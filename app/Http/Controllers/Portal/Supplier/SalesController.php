<?php

namespace App\Http\Controllers\Portal\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalesController extends Controller
{
    /** 매장별 발주 상세 팝업 (자사 품목 기준, HTML fragment) */
    public function storeOrders(Request $request, Store $store)
    {
        $sid = Auth::user()->supplier_id;
        $period = $request->query('period', 'all');
        $mine = fn ($q) => $q->where('supplier_id', $sid)->where('supply_type', 'supplier');

        $query = Order::where('store_id', $store->id)
            ->where('status', '!=', 'canceled')
            ->whereHas('items', $mine)
            ->with(['items' => $mine])
            ->latest();
        if ($period === 'month') {
            $query->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month);
        }
        $orders = $query->get();

        return view('portal.shared.sales_store_orders', [
            'store' => $store,
            'orders' => $orders,
            'detailRoute' => 'portal.supplier.orders.show',
            'showCost' => false,
        ]);
    }

    public function index(Request $request)
    {
        $sid = Auth::user()->supplier_id;
        $period = $request->query('period', 'all');
        $from = $request->query('from') ?: null;
        $to = $request->query('to') ?: null;
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $base = function () use ($sid, $period, $from, $to) {
            $q = OrderItem::query()
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('order_items.supplier_id', $sid)
                ->where('order_items.supply_type', 'supplier')
                ->where('orders.status', '!=', 'canceled');
            if ($from) {
                $q->whereDate('order_items.created_at', '>=', $from);
            }
            if ($to) {
                $q->whereDate('order_items.created_at', '<=', $to);
            }
            if (! $from && ! $to && $period === 'month') {
                $q->whereYear('order_items.created_at', now()->year)
                  ->whereMonth('order_items.created_at', now()->month);
            }

            return $q;
        };

        $totals = [
            'amount' => (int) $base()->sum('order_items.supply_line_amount'), // 자사 총 공급액 (본사 청구)
            'qty' => (int) $base()->sum('order_items.qty'),
            'items' => $base()->count(),
            'delivered' => (clone $base())->where('order_items.fulfillment_status', 'delivered')->sum('order_items.supply_line_amount'),
        ];
        $totals['delivered'] = (int) $totals['delivered'];

        // 매장별 공급액
        $byStore = $base()
            ->join('stores', 'stores.id', '=', 'orders.store_id')
            ->selectRaw('stores.id, stores.name, stores.region, count(*) as items, sum(order_items.qty) as qty, sum(order_items.supply_line_amount) as amount')
            ->groupBy('stores.id', 'stores.name', 'stores.region')
            ->orderByDesc('amount')
            ->get();

        return view('portal.supplier.sales', compact('totals', 'byStore', 'period', 'from', 'to'));
    }
}
