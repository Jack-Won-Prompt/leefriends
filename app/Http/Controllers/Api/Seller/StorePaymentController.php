<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 모바일 앱 — 매장별 입금현황(총 발주액 대비 입금완료/미입금, order.paid_at 기준).
 * 웹 Portal\Hq\StorePaymentController 와 동일 로직. 본사(hq) 전용.
 */
class StorePaymentController extends Controller
{
    use ResolvesSeller;

    private function guardHq(Request $request): void
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->guardHq($request);
        [$period, $from, $to, $year, $month] = $this->resolvePeriod($request);

        $base = fn () => $this->apply(
            Order::query()->where('order_type', 'normal')->where('status', '!=', 'canceled'),
            $period, $from, $to
        );

        $total = (int) $base()->sum(DB::raw('store_amount + shipping_fee'));
        $paid = (int) $base()->whereNotNull('paid_at')->sum(DB::raw('store_amount + shipping_fee'));

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

        return response()->json([
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'totals' => [
                'total' => $total,
                'paid' => $paid,
                'unpaid' => $total - $paid,
                'unpaid_cnt' => $base()->whereNull('paid_at')->count(),
            ],
            'stores' => $byStore->map(fn ($s) => [
                'id' => (int) $s->id,
                'name' => $s->name,
                'region' => $s->region,
                'cnt' => (int) $s->cnt,
                'total' => (int) $s->total,
                'paid' => (int) $s->paid,
                'unpaid' => (int) $s->total - (int) $s->paid,
                'unpaid_cnt' => (int) $s->unpaid_cnt,
                'last_paid_at' => $s->last_paid_at ? substr((string) $s->last_paid_at, 0, 10) : null,
            ])->all(),
        ]);
    }

    public function show(Request $request, Store $store): JsonResponse
    {
        $this->guardHq($request);
        [$period, $from, $to] = $this->resolvePeriod($request);

        $orders = $this->apply(
            Order::where('store_id', $store->id)->where('order_type', 'normal')->where('status', '!=', 'canceled'),
            $period, $from, $to
        )->withCount('items')->orderByRaw('paid_at is null desc')->latest()->get();

        return response()->json([
            'store' => ['id' => $store->id, 'name' => $store->name],
            'orders' => $orders->map(fn (Order $o) => [
                'id' => $o->id,
                'order_no' => $o->order_no,
                'status' => $o->status,
                'status_label' => Order::STATUSES[$o->status] ?? $o->status,
                'item_count' => $o->items_count,
                'total' => (int) $o->order_total,
                'paid' => $o->isPaid(),
                'paid_at' => $o->paid_at?->format('Y-m-d'),
                'created_at' => $o->created_at?->format('Y-m-d'),
            ])->all(),
        ]);
    }

    public function requestUnpaid(Request $request, Store $store, \App\Services\Order\PaymentRequestSms $sms): JsonResponse
    {
        $this->guardHq($request);
        [$period, $from, $to] = $this->resolvePeriod($request);

        $q = $this->apply(
            Order::where('store_id', $store->id)->where('order_type', 'normal')
                ->where('status', '!=', 'canceled')->whereNull('paid_at'),
            $period, $from, $to
        );
        $amount = (int) (clone $q)->sum(DB::raw('store_amount + shipping_fee'));
        $count = (clone $q)->count();

        if ($count === 0) {
            return response()->json(['message' => '미입금 발주가 없습니다.'], 422);
        }

        try {
            $sms->dispatchUnpaidSummary($store, $amount, $count);
        } catch (\Throwable $e) {
            return response()->json(['message' => '미입금 안내 SMS 전송 실패: '.$e->getMessage()], 422);
        }

        return response()->json([
            'message' => "{$store->name}에 미입금 ".number_format($count).'건 · '.number_format($amount).'원 안내 SMS를 전송했습니다.',
        ]);
    }

    /** [period, from, to, year, month] 해석 (웹과 동일한 month 선택 처리) */
    private function resolvePeriod(Request $request): array
    {
        $period = $request->query('period', 'all');
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month');
        $from = $request->query('from') ?: null;
        $to = $request->query('to') ?: null;
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }
        if ($month >= 1 && $month <= 12) {
            $from = sprintf('%04d-%02d-01', $year, $month);
            $to = date('Y-m-t', strtotime($from));
            $period = 'month_sel';
        }

        return [$period, $from, $to, $year, $month];
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
}
