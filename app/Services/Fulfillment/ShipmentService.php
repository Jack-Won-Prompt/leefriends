<?php

namespace App\Services\Fulfillment;

use App\Models\OrderItem;
use App\Models\Shipment;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\DB;

class ShipmentService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * 확인된 판매주문 품목으로 출고 생성 (한 매장, 한 판매자).
     *
     * @param  array<int>  $itemIds  order_items.id
     */
    public function create(string $sellerType, ?int $supplierId, int $storeId, array $itemIds, ?string $note = null): Shipment
    {
        return DB::transaction(function () use ($sellerType, $supplierId, $storeId, $itemIds, $note) {
            // 대상 품목 검증: 확인된 판매주문 + 미출고 + 동일 매장/판매자
            $items = OrderItem::whereIn('order_items.id', $itemIds)
                ->whereNull('shipment_id')
                ->whereHas('salesOrder', fn ($q) => $q
                    ->where('store_id', $storeId)
                    ->where('seller_type', $sellerType)
                    ->where('supplier_id', $supplierId)
                    ->where('status', 'confirmed'))
                ->get();

            abort_if($items->isEmpty(), 400, '출고 가능한 품목이 없습니다.');

            $shipment = Shipment::create([
                'shipment_no' => $this->generateNo($sellerType),
                'seller_type' => $sellerType,
                'supplier_id' => $supplierId,
                'store_id' => $storeId,
                'status' => 'created',
                'item_count' => $items->count(),
                'total_qty' => (int) $items->sum('qty'),
                'supply_amount' => (int) $items->sum('supply_line_amount'),
                'note' => $note,
            ]);

            OrderItem::whereIn('id', $items->pluck('id'))->update(['shipment_id' => $shipment->id]);

            return $shipment;
        });
    }

    /** 송장 입력 + 출고확정 → 배송시작. 매장에 FCM 알림. */
    public function confirm(Shipment $shipment, string $carrier, string $trackingNo): void
    {
        abort_unless($shipment->status === 'created', 400, '이미 확정되었거나 처리할 수 없는 출고입니다.');

        DB::transaction(function () use ($shipment, $carrier, $trackingNo) {
            $shipment->update([
                'carrier' => $carrier,
                'tracking_no' => $trackingNo,
                'status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            // 품목 배송중 처리
            $shipment->items()->update(['fulfillment_status' => 'shipping', 'shipped_at' => now()]);

            // 판매주문/구매주문 상태 동기화
            foreach ($shipment->items()->with('order')->get()->groupBy('sales_order_id') as $soId => $items) {
                if ($soId) {
                    \App\Models\SalesOrder::where('id', $soId)->update(['status' => 'shipped']);
                }
            }
            $orderIds = $shipment->items()->distinct()->pluck('order_id');
            foreach (\App\Models\Order::whereIn('id', $orderIds)->get() as $order) {
                $order->syncStatus();
            }
        });

        // 매장 알림 (배송시작 + 송장)
        $this->notifications->notifyStore(
            $shipment->store_id,
            'shipment_confirmed',
            '🚚 배송이 시작되었습니다',
            "{$shipment->seller_name} 출고 {$shipment->shipment_no} · {$carrier} {$trackingNo}",
            [
                'shipment_id' => $shipment->id,
                'shipment_no' => $shipment->shipment_no,
                'carrier' => $carrier,
                'tracking_no' => $trackingNo,
            ]
        );
    }

    private function generateNo(string $sellerType): string
    {
        $prefix = $sellerType === 'supplier' ? 'SP' : 'HQ';
        $date = now()->format('Ymd');
        $seq = Shipment::whereDate('created_at', today())->count() + 1;

        return sprintf('SHP-%s-%s-%03d', $prefix, $date, $seq);
    }
}
