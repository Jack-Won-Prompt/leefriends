<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 매장 매입 내역 — 기간별 합계/건수 + 주문 목록.
 */
class PurchaseController extends Controller
{
    /**
     * GET /api/v1/purchases?period=all|month&from=&to=
     */
    public function index(Request $request): JsonResponse
    {
        $storeId = $request->user()->store_id;
        abort_unless($storeId, 403, '연결된 매장이 없는 계정입니다.');

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
            'amount' => (int) $apply(Order::query())->sum('store_amount'),
            'orders' => $apply(Order::query())->count(),
        ];

        $orders = $apply(Order::query())->withCount('items')->latest()->paginate(15);

        return response()->json([
            'totals' => $totals,
            'data' => $orders->getCollection()->map(fn (Order $o) => [
                'id' => $o->id,
                'order_no' => $o->order_no,
                'status' => $o->status,
                'status_label' => Order::STATUSES[$o->status] ?? $o->status,
                'item_count' => $o->items_count,
                'store_amount' => (int) $o->store_amount,
                'created_at' => $o->created_at?->format('Y-m-d H:i'),
            ])->values(),
            'meta' => [
                'period' => $period,
                'from' => $from,
                'to' => $to,
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }
}
