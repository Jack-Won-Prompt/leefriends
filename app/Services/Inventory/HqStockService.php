<?php

namespace App\Services\Inventory;

use App\Models\HqInventory;
use App\Models\HqInventoryMovement;
use Illuminate\Support\Facades\DB;

/**
 * 본사 재고 서비스 — 입고/예약(출고예정)/해제/출고/조정.
 * 규칙: HQ 재고 레코드가 있는 품목만 예약·차단 대상(레코드 없으면 '재고 미설정'으로 추적 안 함).
 */
class HqStockService
{
    /** 재고 레코드가 있는 품목의 가용재고. 레코드 없으면 null(미설정) */
    public function available(int $productId): ?int
    {
        $inv = HqInventory::where('supply_product_id', $productId)->first();

        return $inv ? $inv->available : null;
    }

    /**
     * 발주 라인의 가용재고 부족분을 반환.
     * @param array $lines [['product_id'=>int,'qty'=>int,'name'=>string], ...]
     * @return array 부족 품목 [['name'=>, 'available'=>, 'requested'=>], ...]
     */
    public function shortages(array $lines): array
    {
        $need = $this->aggregate($lines);
        if (empty($need)) {
            return [];
        }

        $invs = HqInventory::whereIn('supply_product_id', array_keys($need))->get()->keyBy('supply_product_id');
        $short = [];
        foreach ($need as $pid => $info) {
            $inv = $invs->get($pid);
            if ($inv && $inv->available < $info['qty']) {
                $short[] = ['name' => $inv->product_name, 'available' => $inv->available, 'requested' => $info['qty']];
            }
        }

        return $short;
    }

    /** 입고(실물 +) */
    public function inbound(int $productId, string $name, int $qty, ?string $source = 'manual', ?string $refType = null, ?int $refId = null, ?int $userId = null, ?string $note = null): void
    {
        if ($qty <= 0) {
            return;
        }
        $this->apply($productId, $name, 'inbound', $qty, 0, $source, $refType, $refId, $userId, $note);
    }

    /** 실사 조정(실물 목표치로 설정) */
    public function adjust(int $productId, string $name, int $newQty, ?int $userId = null, ?string $note = null): void
    {
        $inv = $this->firstOrNew($productId, $name);
        $delta = $newQty - (int) $inv->qty;
        $this->apply($productId, $name, 'adjust', $delta, 0, 'manual', null, null, $userId, $note);
    }

    /** 예약(출고예정 +) — 레코드 있는 품목만 */
    public function reserve(array $lines, ?string $refType = null, ?int $refId = null, ?int $userId = null): void
    {
        foreach ($this->aggregate($lines) as $pid => $info) {
            $inv = HqInventory::where('supply_product_id', $pid)->first();
            if (! $inv) {
                continue; // 미설정 품목은 예약 안 함
            }
            $this->apply($pid, $inv->product_name, 'reserve', 0, $info['qty'], 'order', $refType, $refId, $userId, null);
        }
    }

    /** 예약 해제(출고예정 -) */
    public function release(array $lines, ?string $refType = null, ?int $refId = null, ?int $userId = null): void
    {
        foreach ($this->aggregate($lines) as $pid => $info) {
            $inv = HqInventory::where('supply_product_id', $pid)->first();
            if (! $inv) {
                continue;
            }
            $rel = min($info['qty'], (int) $inv->reserved_qty);
            if ($rel <= 0) {
                continue;
            }
            $this->apply($pid, $inv->product_name, 'release', 0, -$rel, 'order', $refType, $refId, $userId, null);
        }
    }

    /** 출고(실물 - , 예약 -) */
    public function ship(array $lines, ?string $refType = null, ?int $refId = null, ?int $userId = null): void
    {
        foreach ($this->aggregate($lines) as $pid => $info) {
            $inv = HqInventory::where('supply_product_id', $pid)->first();
            if (! $inv) {
                continue;
            }
            $rel = min($info['qty'], (int) $inv->reserved_qty);
            $this->apply($pid, $inv->product_name, 'ship', -$info['qty'], -$rel, 'shipment', $refType, $refId, $userId, null);
        }
    }

    /** @return array<int, array{qty:int, name:string}> product_id => 합계 */
    private function aggregate(array $lines): array
    {
        $out = [];
        foreach ($lines as $l) {
            $pid = (int) ($l['product_id'] ?? 0);
            $qty = (int) ($l['qty'] ?? 0);
            if ($pid <= 0 || $qty <= 0) {
                continue;
            }
            if (! isset($out[$pid])) {
                $out[$pid] = ['qty' => 0, 'name' => $l['name'] ?? ''];
            }
            $out[$pid]['qty'] += $qty;
        }

        return $out;
    }

    private function firstOrNew(int $productId, string $name): HqInventory
    {
        return HqInventory::firstOrCreate(
            ['supply_product_id' => $productId],
            ['product_name' => $name, 'qty' => 0, 'reserved_qty' => 0]
        );
    }

    private function apply(int $productId, string $name, string $type, int $qtyDelta, int $reservedDelta, ?string $source, ?string $refType, ?int $refId, ?int $userId, ?string $note): void
    {
        DB::transaction(function () use ($productId, $name, $type, $qtyDelta, $reservedDelta, $source, $refType, $refId, $userId, $note) {
            $inv = HqInventory::where('supply_product_id', $productId)->lockForUpdate()->first();
            if (! $inv) {
                $inv = HqInventory::create(['supply_product_id' => $productId, 'product_name' => $name, 'qty' => 0, 'reserved_qty' => 0]);
            }
            $inv->qty += $qtyDelta;
            $inv->reserved_qty = max(0, $inv->reserved_qty + $reservedDelta);
            $inv->save();

            HqInventoryMovement::create([
                'supply_product_id' => $productId,
                'product_name' => $inv->product_name,
                'type' => $type,
                'qty_delta' => $qtyDelta,
                'reserved_delta' => $reservedDelta,
                'balance_qty' => $inv->qty,
                'balance_reserved' => $inv->reserved_qty,
                'source' => $source,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'user_id' => $userId,
                'note' => $note,
            ]);
        });
    }
}
