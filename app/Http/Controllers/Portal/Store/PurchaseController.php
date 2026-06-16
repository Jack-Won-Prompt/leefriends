<?php

namespace App\Http\Controllers\Portal\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $storeId = Auth::user()->store_id;
        $period = $request->query('period', 'all');
        $from = $request->query('from') ?: null;
        $to = $request->query('to') ?: null;
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $apply = function ($q) use ($storeId, $period, $from, $to) {
            $q->where('store_id', $storeId)->where('status', '!=', 'canceled');
            if ($from) {
                $q->whereDate('created_at', '>=', $from);
            }
            if ($to) {
                $q->whereDate('created_at', '<=', $to);
            }
            if (! $from && ! $to && $period === 'month') {
                $q->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month);
            }

            return $q;
        };

        $totals = [
            'amount' => (int) $apply(Order::query())->sum('store_amount'), // 구매주문 총액
            'orders' => $apply(Order::query())->count(),
        ];

        // 주문별 총액
        $orders = $apply(Order::query())->withCount('items')->latest()->paginate(15)->withQueryString();

        return view('portal.store.purchases', compact('totals', 'orders', 'period', 'from', 'to'));
    }
}
