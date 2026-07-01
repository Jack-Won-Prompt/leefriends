<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;

/**
 * 매장별 입금현황 — 총 발주액 대비 입금완료/미입금 집계 (order.paid_at 기준).
 */
class StorePaymentController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->query('period', 'all');
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month');
        [$from, $to] = $this->dateRange($request);
        if ($month >= 1 && $month <= 12) {
            $from = sprintf('%04d-%02d-01', $year, $month);
            $to = date('Y-m-t', strtotime($from));
            $period = 'month_sel';
        }

        $base = fn () => $this->apply(
            Order::query()->where('order_type', 'normal')->where('status', '!=', 'canceled'),
            $period, $from, $to
        );

        // 전체 요약
        $total = (int) $base()->sum(\DB::raw('store_amount + shipping_fee'));
        $paid = (int) $base()->whereNotNull('paid_at')->sum(\DB::raw('store_amount + shipping_fee'));

        $totals = [
            'total' => $total,
            'paid' => $paid,
            'unpaid' => $total - $paid,
            'unpaid_cnt' => $base()->whereNull('paid_at')->count(),
        ];

        // 매장별 집계
        $byStore = $this->apply(
            Order::query()->where('orders.order_type', 'normal'),
            $period, $from, $to
        )
            ->join('stores', 'stores.id', '=', 'orders.store_id')
            ->where('orders.status', '!=', 'canceled')
            ->selectRaw('stores.id, stores.name, stores.region,
                count(*) as cnt,
                sum(orders.store_amount + orders.shipping_fee) as total,
                sum(case when orders.paid_at is not null then orders.store_amount + orders.shipping_fee else 0 end) as paid,
                sum(case when orders.paid_at is null then 1 else 0 end) as unpaid_cnt,
                max(orders.paid_at) as last_paid_at')
            ->groupBy('stores.id', 'stores.name', 'stores.region')
            ->orderByDesc('unpaid_cnt')
            ->orderByDesc('total')
            ->get();

        return view('portal.hq.store_payments.index', compact('totals', 'byStore', 'period', 'from', 'to', 'year', 'month'));
    }

    /** 매장 드릴다운 — 미입금/입금완료 발주 목록 */
    public function show(Request $request, Store $store)
    {
        $period = $request->query('period', 'all');
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month');
        [$from, $to] = $this->dateRange($request);
        if ($month >= 1 && $month <= 12) {
            $from = sprintf('%04d-%02d-01', $year, $month);
            $to = date('Y-m-t', strtotime($from));
            $period = 'month_sel';
        }

        $orders = $this->apply(
            Order::where('store_id', $store->id)->where('order_type', 'normal')->where('status', '!=', 'canceled'),
            $period, $from, $to
        )->withCount('items')->orderByRaw('paid_at is null desc')->latest()->get();

        return view('portal.hq.store_payments.show', [
            'store' => $store,
            'orders' => $orders,
            'period' => $period,
            'from' => $from,
            'to' => $to,
            'year' => $year,
            'month' => $month,
        ]);
    }

    private function apply($q, string $period, ?string $from, ?string $to)
    {
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
    }

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
