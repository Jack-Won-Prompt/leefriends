<?php

namespace App\Services\Fulfillment;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SalesOrder;

/**
 * 구매주문(Order)을 이행 주체(본사/공급처)별 판매주문(SalesOrder)으로 분할 생성.
 */
class SalesOrderGenerator
{
    public function generate(Order $order): void
    {
        $order->loadMissing('items');

        // 이행 주체별 그룹핑: 본사(hq) / 공급처(supplier:{id})
        $groups = $order->items->groupBy(function ($item) {
            return $item->supply_type === 'supplier'
                ? 'supplier:' . $item->supplier_id
                : 'hq';
        });

        $seq = 0;
        foreach ($groups as $key => $items) {
            $isSupplier = str_starts_with((string) $key, 'supplier:');
            $supplierId = $isSupplier ? (int) explode(':', $key)[1] : null;

            $salesOrder = SalesOrder::create([
                'sales_order_no' => $this->generateNo($order, ++$seq),
                'order_id' => $order->id,
                'store_id' => $order->store_id,
                'seller_type' => $isSupplier ? 'supplier' : 'hq',
                'supplier_id' => $supplierId,
                'status' => 'created',
                'order_type' => $order->order_type ?? 'normal',
                'item_count' => $items->count(),
                'store_amount' => (int) $items->sum('store_line_amount'),
                'supply_amount' => (int) $items->sum('supply_line_amount'),
            ]);

            OrderItem::whereIn('id', $items->pluck('id'))->update(['sales_order_id' => $salesOrder->id]);
        }
    }

    private function generateNo(Order $order, int $seq): string
    {
        $base = preg_replace('/^PO-/', '', $order->order_no);

        return sprintf('SO-%s-%d', $base, $seq);
    }
}
