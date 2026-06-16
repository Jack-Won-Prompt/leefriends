@extends('portal.layout')
@section('title', '입고예정 · 배송')

@section('content')
<x-wms.page-head title="입고예정 · 배송" subtitle="배송중 출고를 인수·입고하고, 입고예정을 확인합니다" icon="🚚" />

{{-- 배송중 (출고확정, 송장 있음) --}}
<h2 class="text-base font-extrabold text-neutral-900 mb-3">🚚 배송중 (입고 대기)</h2>
<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden mb-8">
    @if ($inTransit->isEmpty())
        <p class="px-6 py-10 text-center text-neutral-400">배송중인 출고가 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">출고번호</th>
                        <th class="text-left font-semibold px-6 py-3">공급</th>
                        <th class="text-left font-semibold px-6 py-3">송장</th>
                        <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">품목/수량</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">출고일</th>
                        <th class="text-right font-semibold px-6 py-3 w-32">처리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($inTransit as $s)
                        <tr class="hover:bg-mango-50/40 transition">
                            <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $s->shipment_no }}</td>
                            <td class="px-6 py-3.5">{{ $s->seller_name }}</td>
                            <td class="px-6 py-3.5"><span class="text-neutral-700">{{ $s->carrier }}</span> <span class="font-bold text-mango-700">{{ $s->tracking_no }}</span></td>
                            <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ $s->item_count }}건 / {{ number_format($s->total_qty) }}</td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $s->confirmed_at?->format('Y.m.d H:i') }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <a href="{{ route('portal.store.shipments.show', $s) }}" class="rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1.5 font-semibold inline-block">입고 처리</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- 입고예정 (판매주문 확인됨, 출고 전) --}}
<h2 class="text-lg font-extrabold text-neutral-900 mb-3">📋 입고예정 (출고 대기)</h2>
<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    @if ($expected->isEmpty())
        <p class="px-6 py-10 text-center text-neutral-400">입고예정 정보가 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">판매주문</th>
                        <th class="text-left font-semibold px-6 py-3">공급</th>
                        <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">품목</th>
                        <th class="text-left font-semibold px-6 py-3">상태</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">확인일</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($expected as $so)
                        <tr class="hover:bg-mango-50/40 transition">
                            <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $so->sales_order_no }}
                                <span class="block text-xs text-neutral-400">{{ $so->order->order_no ?? '' }}</span>
                            </td>
                            <td class="px-6 py-3.5">{{ $so->seller_name }}</td>
                            <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ $so->item_count }}</td>
                            <td class="px-6 py-3.5"><span class="text-xs font-bold px-2.5 py-1 rounded-full bg-sky-100 text-sky-700">입고예정</span></td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $so->confirmed_at?->format('Y.m.d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
