<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    /** 매장별 발주 상세 팝업 (HTML fragment) */
    public function storeOrders(Request $request, Store $store)
    {
        $period = $request->query('period', 'all');

        $query = Order::where('store_id', $store->id)
            ->where('status', '!=', 'canceled')
            ->withCount('items')
            ->latest();
        if ($period === 'month') {
            $query->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month);
        }
        $orders = $query->get();

        return view('portal.shared.sales_store_orders', [
            'store' => $store,
            'orders' => $orders,
            'detailRoute' => 'portal.hq.orders.show',
            'showCost' => true,
        ]);
    }

    public function index(Request $request)
    {
        $period = $request->query('period', 'all');
        [$from, $to] = $this->dateRange($request);

        $apply = function ($q) use ($period, $from, $to) {
            $q->where('orders.status', '!=', 'canceled');
            if ($from) {
                $q->whereDate('orders.created_at', '>=', $from);
            }
            if ($to) {
                $q->whereDate('orders.created_at', '<=', $to);
            }
            if (! $from && ! $to && $period === 'month') {
                $q->whereYear('orders.created_at', now()->year)->whereMonth('orders.created_at', now()->month);
            }

            return $q;
        };

        $totals = [
            'sales' => (int) $apply(Order::query())->sum('store_amount'),   // 총 판매액 (매장 결제)
            'cost' => (int) $apply(Order::query())->sum('supply_amount'),   // 총 공급원가
            'orders' => $apply(Order::query())->count(),
        ];
        $totals['margin'] = $totals['sales'] - $totals['cost'];

        // 매장별 매출
        $byStore = $apply(Order::query())
            ->join('stores', 'stores.id', '=', 'orders.store_id')
            ->selectRaw('stores.id, stores.name, stores.region, count(*) as cnt, sum(orders.store_amount) as sales, sum(orders.supply_amount) as cost')
            ->groupBy('stores.id', 'stores.name', 'stores.region')
            ->orderByDesc('sales')
            ->get();

        return view('portal.hq.sales', compact('totals', 'byStore', 'period', 'from', 'to'));
    }

    /** from/to 쿼리 파라미터를 정규화 (from > to 이면 교환) */
    private function dateRange(Request $request): array
    {
        $from = $request->query('from') ?: null;
        $to = $request->query('to') ?: null;
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
