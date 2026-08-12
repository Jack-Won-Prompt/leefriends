@extends('portal.layout')
@section('title', $store->name.' 입금현황')

@section('content')
@php
    $paidAmt = $orders->whereNotNull('paid_at')->sum(fn ($o) => $o->order_total);
    $unpaidOrders = $orders->whereNull('paid_at');
    $unpaidAmt = $unpaidOrders->sum(fn ($o) => $o->order_total);
@endphp

<x-wms.page-head :title="$store->name.' 입금현황'" :subtitle="($store->region ?: '').' · 발주별 입금 상태'" icon="🏪">
    <x-slot:actions>
        <a href="{{ route('portal.hq.store_payments.excel', ['store' => $store, 'period' => $period, 'from' => $from, 'to' => $to, 'year' => $year, 'month' => $month]) }}"
           class="inline-flex items-center gap-1 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 text-sm">⬇️ 엑셀 (입금·거래내역)</a>
        <a href="{{ route('portal.hq.store_payments.index', ['period' => $period, 'from' => $from, 'to' => $to, 'year' => $year, 'month' => $month]) }}"
           class="inline-flex items-center gap-1 rounded-xl border border-neutral-200 hover:bg-neutral-50 font-bold px-4 py-2 text-sm">← 목록</a>
    </x-slot:actions>
</x-wms.page-head>

{{-- 날짜 필터: 전체 + 월별(1~12월) + 기간 조회 (엑셀 다운로드에도 반영됨) --}}
@php
    $btn = 'inline-flex items-center justify-center rounded-xl px-3.5 py-2 text-sm font-bold transition';
    $isRange = ! $month && ($from || $to);
@endphp
<div class="flex flex-wrap items-end justify-between gap-4 mb-6">
    <div class="flex flex-wrap items-center gap-1.5">
        <a href="{{ route('portal.hq.store_payments.show', $store) }}"
           class="{{ $btn }} {{ ! $month && ! $isRange ? 'bg-mango-500 text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">전체</a>
        <span class="mx-1 text-neutral-300">|</span>
        <span class="text-sm font-bold text-neutral-500 mr-1">{{ $year }}년</span>
        @for ($m = 1; $m <= 12; $m++)
            <a href="{{ route('portal.hq.store_payments.show', ['store' => $store, 'year' => $year, 'month' => $m]) }}"
               class="{{ $btn }} {{ (int) $month === $m ? 'bg-mango-500 text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">{{ $m }}월</a>
        @endfor
    </div>
    <form method="GET" action="{{ route('portal.hq.store_payments.show', $store) }}"
          class="flex items-end gap-2 {{ $isRange ? 'ring-2 ring-mango-300 rounded-xl p-2' : '' }}">
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">시작일</label>
            <input type="date" name="from" value="{{ $isRange ? $from : '' }}" class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">종료일</label>
            <input type="date" name="to" value="{{ $isRange ? $to : '' }}" class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <button type="submit" class="rounded-xl bg-neutral-800 hover:bg-neutral-900 text-white font-bold px-4 py-2 text-sm transition">기간 조회</button>
    </form>
</div>

<div class="grid grid-cols-3 gap-4 mb-6">
    <x-wms.stat label="총 발주액" :value="number_format($orders->sum(fn($o)=>$o->order_total)).'원'" variant="default" />
    <x-wms.stat label="입금완료" :value="number_format($paidAmt).'원'" variant="success" icon="💰" />
    <x-wms.stat label="미입금" :value="number_format($unpaidAmt).'원'" :sub="number_format($unpaidOrders->count()).'건'" variant="warn" />
</div>

<x-wms.panel>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-6 py-3">주문번호</th>
                <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">품목</th>
                <th class="text-right font-semibold px-6 py-3">발주액</th>
                <th class="text-left font-semibold px-6 py-3">상태</th>
                <th class="text-left font-semibold px-6 py-3">입금</th>
                <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">발주일</th>
                <th class="text-right font-semibold px-6 py-3">입금요청</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($orders as $o)
                <tr class="hover:bg-mango-50/40 transition">
                    <td class="px-6 py-3.5 font-bold text-neutral-900">
                        <a href="{{ route('portal.hq.orders.show', $o) }}" class="hover:underline">{{ $o->order_no }}</a>
                    </td>
                    <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ $o->items_count }}</td>
                    <td class="px-6 py-3.5 text-right font-semibold tabular-nums">{{ number_format($o->order_total) }}원</td>
                    <td class="px-6 py-3.5">@include('portal.partials.order-status', ['status' => $o->status, 'label' => $o->status_label])</td>
                    <td class="px-6 py-3.5">@include('portal.partials.payment-badge', ['order' => $o])</td>
                    <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $o->created_at->format('Y.m.d') }}</td>
                    <td class="px-6 py-3.5 text-right">
                        @if ($o->paid_at)
                            <span class="text-xs text-emerald-600 font-bold">완료</span>
                        @else
                            <form method="POST" action="{{ route('portal.hq.orders.payment_request', $o) }}"
                                  data-confirm="{{ $store->name }}({{ $store->phone ?? '번호없음' }})에 입금요청 SMS를 전송합니다.\n발주금액 {{ number_format($o->order_total) }}원\n진행하시겠습니까?">
                                @csrf
                                <button type="submit" @unless ($store->phone) disabled @endunless
                                        class="inline-flex items-center gap-1 rounded-lg bg-mango-500 hover:bg-mango-600 disabled:opacity-40 text-white font-bold px-3 py-1.5 text-xs transition">💬 입금요청</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-6 py-16 text-center text-neutral-400">해당 기간 발주가 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-wms.panel>
@endsection
