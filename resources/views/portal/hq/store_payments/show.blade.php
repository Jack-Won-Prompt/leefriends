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
        <a href="{{ route('portal.hq.store_payments.index', ['period' => $period, 'from' => $from, 'to' => $to]) }}"
           class="inline-flex items-center gap-1 rounded-xl border border-neutral-200 hover:bg-neutral-50 font-bold px-4 py-2 text-sm">← 목록</a>
    </x-slot:actions>
</x-wms.page-head>

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
                                  onsubmit="return confirm('{{ $store->name }}({{ $store->phone ?? '번호없음' }})에 입금요청 SMS를 전송합니다.\n발주금액 {{ number_format($o->order_total) }}원\n진행하시겠습니까?')">
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
