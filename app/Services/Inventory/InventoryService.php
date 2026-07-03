<?php

namespace App\Services\Inventory;

use App\Models\InventoryMovement;
use App\Models\Shipment;
use App\Models\StoreInventory;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * 출고(배송) 인수확인 → 입고완료. 재고 가산 + 이동내역(in) 기록.
     */
    public function receiveShipment(Shipment $shipment, ?int $userId = null): void
    {
        abort_unless(in_array($shipment->status, ['confirmed', 'delivered'], true), 400, '배송중·배송완료 상태의 출고만 입고할 수 있습니다.');

        DB::transaction(function () use ($shipment, $userId) {
            foreach ($shipment->items()->get() as $item) {
                $this->apply(
                    storeId: $shipment->store_id,
                    productId: $item->supply_product_id,
                    unitId: $item->supply_product_unit_id,
                    productName: $item->product_name,
                    unitName: $item->unit,
                    delta: $item->qty,
                    type: 'in',
                    source: 'inbound',
                    shipmentId: $shipment->id,
                    userId: $userId,
                    note: "입고 {$shipment->shipment_no}",
                );
                $item->update(['fulfillment_status' => 'delivered']);
            }

            $shipment->update([
                'status' => 'received',
                'received_at' => now(),
                'received_by' => $userId,
            ]);

            // 판매주문/구매주문 동기화
            foreach ($shipment->items()->distinct()->pluck('sales_order_id') as $soId) {
                if ($soId && ! \App\Models\OrderItem::where('sales_order_id', $soId)->where('fulfillment_status', '!=', 'delivered')->exists()) {
                    \App\Models\SalesOrder::where('id', $soId)->update(['status' => 'received']);
                }
            }
            foreach (\App\Models\Order::whereIn('id', $shipment->items()->distinct()->pluck('order_id'))->get() as $order) {
                $order->syncStatus();
            }
        });
    }

    /**
     * 매장 재고 사용(출고) → 재고 차감 + 이동내역(out).
     */
    public function useStock(int $storeId, int $productId, ?int $unitId, int $qty, ?int $userId = null, ?string $note = null): StoreInventory
    {
        abort_if($qty <= 0, 400, '수량은 1 이상이어야 합니다.');

        return DB::transaction(function () use ($storeId, $productId, $unitId, $qty, $userId, $note) {
            $inv = StoreInventory::where('store_id', $storeId)
                ->where('supply_product_id', $productId)
                ->where('supply_product_unit_id', $unitId)
                ->lockForUpdate()
                ->first();

            abort_if(! $inv || $inv->qty < $qty, 400, '재고가 부족합니다.');

            $this->apply(
                storeId: $storeId,
                productId: $productId,
                unitId: $unitId,
                productName: $inv->product_name,
                unitName: $inv->unit_name,
                delta: -$qty,
                type: 'out',
                source: 'usage',
                shipmentId: null,
                userId: $userId,
                note: $note ?? '매장 사용',
            );

            return $inv->fresh();
        });
    }

    /** 재고 반영(가산/차감) + 이동내역 기록 (공통) */
    private function apply(int $storeId, int $productId, ?int $unitId, string $productName, string $unitName, int $delta, string $type, string $source, ?int $shipmentId, ?int $userId, ?string $note): void
    {
        $inv = StoreInventory::firstOrCreate(
            ['store_id' => $storeId, 'supply_product_id' => $productId, 'supply_product_unit_id' => $unitId],
            ['product_name' => $productName, 'unit_name' => $unitName, 'qty' => 0]
        );

        $inv->qty += $delta;
        if ($inv->qty < 0) {
            $inv->qty = 0;
        }
        // 명칭 최신화
        $inv->product_name = $productName;
        $inv->unit_name = $unitName;
        $inv->save();

        InventoryMovement::create([
            'store_id' => $storeId,
            'supply_product_id' => $productId,
            'supply_product_unit_id' => $unitId,
            'product_name' => $productName,
            'unit_name' => $unitName,
            'type' => $type,
            'source' => $source,
            'qty' => $delta,
            'balance_after' => $inv->qty,
            'shipment_id' => $shipmentId,
            'user_id' => $userId,
            'note' => $note,
        ]);
    }
}
