@extends('portal.layout')
@section('title', '구매 현황')

@section('content')
<x-wms.page-head title="구매 현황" subtitle="구매주문 총액 · 주문별 총액" icon="📈" />
@include('portal.partials.period-tabs', ['routeName' => 'portal.store.purchases', 'period' => $period])

{{-- 요약 --}}
<div class="grid grid-cols-2 gap-4 mb-8">
    <div class="rounded-2xl bg-gradient-to-br from-mango-500 to-mango-600 text-white p-6">
        <p class="text-white/80 font-semibold text-sm">구매주문 총액</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totals['amount']) }}<span class="text-lg">원</span></p>
        <p class="text-white/70 text-xs mt-1">발주 {{ number_format($totals['orders']) }}건 (취소 제외)</p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-neutral-100 flex flex-col justify-center">
        <p class="text-sm text-neutral-500 font-medium">발주 건수</p>
        <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($totals['orders']) }}<span class="text-lg">건</span></p>
    </div>
</div>

{{-- 주문별 총액 --}}
<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">주문별 총액</div>
    @if ($orders->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">구매 내역이 없습니다.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">주문번호</th>
                    <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">품목수</th>
                    <th class="text-right font-semibold px-6 py-3">주문 총액</th>
                    <th class="text-left font-semibold px-6 py-3">상태</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">발주일</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($orders as $o)
                    <tr class="hover:bg-mango-50/40 transition cursor-pointer" onclick="location.href='{{ route('portal.store.orders.show', $o) }}'">
                        <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $o->order_no }}</td>
                        <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ $o->items_count }}</td>
                        <td class="px-6 py-3.5 text-right font-black text-mango-700">{{ number_format($o->store_amount) }}원</td>
                        <td class="px-6 py-3.5">@include('portal.partials.order-status', ['status' => $o->status, 'label' => $o->status_label])</td>
                        <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $o->created_at->format('Y.m.d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-neutral-50 font-black">
                    <td class="px-6 py-4" colspan="2">합계 (현재 페이지 제외, 전체 기준)</td>
                    <td class="px-6 py-4 text-right text-mango-700">{{ number_format($totals['amount']) }}원</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    @endif
</div>

<div class="mt-6">{{ $orders->links() }}</div>
@endsection
