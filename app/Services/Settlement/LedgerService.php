<?php

namespace App\Services\Settlement;

use App\Models\Order;
use App\Models\Store;
use App\Models\StoreLedgerEntry;
use Illuminate\Support\Facades\DB;

/**
 * 매장 거래 원장(예치금 잔액 · 미수금).
 * 발주는 차감(-), 입금은 충전(+). 잔액 = 예치(+) / 미수(-).
 */
class LedgerService
{
    /** 원장 기록 + 매장 잔액 갱신 (동시성 잠금) */
    public function post(Store $store, string $type, int $amount, array $opts = []): StoreLedgerEntry
    {
        return DB::transaction(function () use ($store, $type, $amount, $opts) {
            $store = Store::whereKey($store->id)->lockForUpdate()->first();
            $balance = (int) $store->ledger_balance + $amount;

            $entry = StoreLedgerEntry::create([
                'store_id' => $store->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balance,
                'source' => $opts['source'] ?? null,
                'ref_type' => $opts['ref_type'] ?? null,
                'ref_id' => $opts['ref_id'] ?? null,
                'memo' => $opts['memo'] ?? null,
                'created_by' => $opts['created_by'] ?? null,
            ]);

            $store->update(['ledger_balance' => $balance]);

            return $entry;
        });
    }

    /** 발주 확정 차감 (중복 방지) */
    public function deductOrder(Order $order, ?int $userId = null): void
    {
        if ($this->hasEntry('order', $order->id)) {
            return;
        }
        $this->post($order->store, 'order', -1 * (int) $order->order_total, [
            'source' => 'order', 'ref_type' => 'order', 'ref_id' => $order->id,
            'memo' => "발주 {$order->order_no}", 'created_by' => $userId,
        ]);
    }

    /** 발주 취소 환불 */
    public function refundOrder(Order $order, ?int $userId = null): void
    {
        // 차감 기록이 있을 때만 환불
        if (! $this->hasEntry('order', $order->id) || $this->hasEntry('refund', $order->id)) {
            return;
        }
        $this->post($order->store, 'refund', (int) $order->order_total, [
            'source' => 'order', 'ref_type' => 'refund', 'ref_id' => $order->id,
            'memo' => "발주 취소 {$order->order_no}", 'created_by' => $userId,
        ]);
    }

    /**
     * 발주의 원장 반영을 현재 상태에 맞게 동기화(멱등).
     * 기록된 순액을 목표(-발주금액, 취소 시 0)로 맞춰 차액만 기록한다.
     * 생성=차감, 수정=증감 조정, 취소=환불을 하나로 처리.
     */
    public function syncOrder(Order $order, ?int $userId = null, bool $createIfMissing = true): void
    {
        $entries = StoreLedgerEntry::where('source', 'order')->where('ref_id', $order->id);
        // 미추적 발주(과거분)는 본사 수정 시 신규 차감을 만들지 않음 (원장 신규 시작 원칙)
        if (! $createIfMissing && ! (clone $entries)->exists()) {
            return;
        }
        $recorded = (int) (clone $entries)->sum('amount');
        $target = $order->status === 'canceled' ? 0 : -1 * (int) $order->order_total;
        $delta = $target - $recorded;
        if ($delta === 0) {
            return;
        }
        $isCharge = $delta > 0; // 잔액 증가(환불/감액)
        $this->post($order->store, $isCharge ? 'refund' : 'order', $delta, [
            'source' => 'order',
            'ref_type' => $isCharge ? 'refund' : 'order',
            'ref_id' => $order->id,
            'memo' => $order->status === 'canceled'
                ? "발주 취소 {$order->order_no}"
                : ($recorded === 0 ? "발주 {$order->order_no}" : "발주 조정 {$order->order_no}"),
            'created_by' => $userId,
        ]);
    }

    /** 입금 충전 (deposit 중복 방지) */
    public function chargeDeposit(Store $store, int $amount, int $depositId, ?string $memo = null): void
    {
        if ($amount <= 0 || $this->depositCharged($depositId)) {
            return;
        }
        $this->post($store, 'charge', $amount, [
            'source' => 'deposit', 'ref_type' => 'deposit', 'ref_id' => $depositId,
            'memo' => $memo ?? '계좌 입금',
        ]);
    }

    /** 수동 충전 */
    public function manualCharge(Store $store, int $amount, ?string $memo, ?int $userId): void
    {
        $this->post($store, 'charge', abs($amount), [
            'source' => 'manual', 'memo' => $memo ?? '수동 충전', 'created_by' => $userId,
        ]);
    }

    /** 잔액 조정 (목표 잔액으로 맞춤) */
    public function adjust(Store $store, int $targetBalance, ?string $memo, ?int $userId): void
    {
        $delta = $targetBalance - (int) $store->fresh()->ledger_balance;
        if ($delta === 0) {
            return;
        }
        $this->post($store, 'adjust', $delta, [
            'source' => 'manual', 'memo' => $memo ?? '잔액 조정', 'created_by' => $userId,
        ]);
    }

    private function hasEntry(string $type, int $orderId): bool
    {
        return StoreLedgerEntry::where('ref_type', $type)->where('ref_id', $orderId)->exists();
    }

    private function depositCharged(int $depositId): bool
    {
        return StoreLedgerEntry::where('ref_type', 'deposit')->where('ref_id', $depositId)->exists();
    }
}
