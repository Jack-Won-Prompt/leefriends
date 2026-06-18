<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 본사/공급처 매출 현황.
 *  - 본사: 매장 판매액/원가/마진
 *  - 공급처: 자사 공급액/수량 (본사 청구 기준)
 */
class SalesController extends Controller
{
    use ResolvesSeller;

    /**
     * GET /api/v1/seller/sales?period=all|month
     */
    public function index(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);
        $period = $request->query('period', 'all') === 'month' ? 'month' : 'all';

        return $type === 'supplier'
            ? $this->supplier($sid, $period)
            : $this->hq($period);
    }

    private function hq(string $period): JsonResponse
    {
        $apply = function ($q) use ($period) {
            $q->where('orders.status', '!=', 'canceled');
            if ($period === 'month') {
                $q->whereYear('orders.created_at', now()->year)
                    ->whereMonth('orders.created_at', now()->month);
            }

            return $q;
        };

        $sales = (int) $apply(Order::query())->sum('store_amount');
        $cost = (int) $apply(Order::query())->sum('supply_amount');
        $orders = $apply(Order::query())->count();

        $byStore = $apply(Order::query())
            ->join('stores', 'stores.id', '=', 'orders.store_id')
            ->selectRaw('stores.name, stores.region, count(*) as qty, sum(orders.store_amount) as amount')
            ->groupBy('stores.name', 'stores.region')
            ->orderByDesc('amount')
            ->get();

        return $this->payload(
            role: 'hq',
            period: $period,
            primaryLabel: '총 판매액',
            primary: $sales,
            secondaryLabel: '마진',
            secondary: $sales - $cost,
            countLabel: '발주',
            count: $orders,
            byStore: $byStore,
            qtyLabel: '건',
        );
    }

    private function supplier(?int $sid, string $period): JsonResponse
    {
        $base = function () use ($sid, $period) {
            $q = OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('order_items.supplier_id', $sid)
                ->where('order_items.supply_type', 'supplier')
                ->where('orders.status', '!=', 'canceled');
            if ($period === 'month') {
                $q->whereYear('order_items.created_at', now()->year)
                    ->whereMonth('order_items.created_at', now()->month);
            }

            return $q;
        };

        $amount = (int) $base()->sum('order_items.supply_line_amount');
        $delivered = (int) (clone $base())->where('order_items.fulfillment_status', 'delivered')
            ->sum('order_items.supply_line_amount');
        $items = $base()->count();

        $byStore = $base()
            ->join('stores', 'stores.id', '=', 'orders.store_id')
            ->selectRaw('stores.name, stores.region, sum(order_items.qty) as qty, sum(order_items.supply_line_amount) as amount')
            ->groupBy('stores.name', 'stores.region')
            ->orderByDesc('amount')
            ->get();

        return $this->payload(
            role: 'supplier',
            period: $period,
            primaryLabel: '총 공급액',
            primary: $amount,
            secondaryLabel: '배송완료',
            secondary: $delivered,
            countLabel: '품목',
            count: $items,
            byStore: $byStore,
            qtyLabel: '개',
        );
    }

    private function payload(
        string $role,
        string $period,
        string $primaryLabel,
        int $primary,
        string $secondaryLabel,
        int $secondary,
        string $countLabel,
        int $count,
        $byStore,
        string $qtyLabel,
    ): JsonResponse {
        return response()->json([
            'data' => [
                'role' => $role,
                'period' => $period,
                'primary_label' => $primaryLabel,
                'primary' => $primary,
                'secondary_label' => $secondaryLabel,
                'secondary' => $secondary,
                'count_label' => $countLabel,
                'count' => $count,
                'qty_label' => $qtyLabel,
                'by_store' => $byStore->map(fn ($r) => [
                    'store_name' => $r->name,
                    'region' => $r->region,
                    'amount' => (int) $r->amount,
                    'qty' => (int) $r->qty,
                ])->values(),
            ],
        ]);
    }
}
