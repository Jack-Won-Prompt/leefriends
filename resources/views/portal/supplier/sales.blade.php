@extends('portal.layout')
@section('title', '매출 현황')

@section('content')
@include('portal.partials.store-orders-modal')
<x-wms.page-head title="매출 현황" subtitle="자사 총 공급액 · 매장별 공급액 (행 클릭 시 발주 상세)" icon="📈" />
@include('portal.partials.period-tabs', ['routeName' => 'portal.supplier.sales', 'period' => $period])

{{-- 요약 --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="rounded-2xl bg-gradient-to-br from-mango-500 to-mango-600 text-white p-6">
        <p class="text-white/80 font-semibold text-sm">총 공급액 (본사 청구)</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totals['amount']) }}<span class="text-lg">원</span></p>
        <p class="text-white/70 text-xs mt-1">공급가 기준</p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-neutral-100">
        <p class="text-sm text-neutral-500 font-medium">배송완료 공급액</p>
        <p class="text-3xl font-black text-emerald-600 mt-1">{{ number_format($totals['delivered']) }}<span class="text-lg">원</span></p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-neutral-100">
        <p class="text-sm text-neutral-500 font-medium">공급 수량</p>
        <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($totals['qty']) }}</p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-neutral-100">
        <p class="text-sm text-neutral-500 font-medium">공급 품목 건수</p>
        <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($totals['items']) }}<span class="text-lg">건</span></p>
    </div>
</div>

{{-- 매장별 공급액 (x-data: 행 클릭 $dispatch 스코프) --}}
<div x-data class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">매장별 공급액 (직배송)</div>
    @if ($byStore->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">집계할 공급 내역이 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3 w-10">#</th>
                        <th class="text-left font-semibold px-6 py-3">매장 (배송지)</th>
                        <th class="text-right font-semibold px-6 py-3">품목</th>
                        <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">수량</th>
                        <th class="text-right font-semibold px-6 py-3">공급액</th>
                        <th class="text-right font-semibold px-6 py-3 hidden lg:table-cell w-32">비중</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($byStore as $i => $row)
                        @php $pct = $totals['amount'] > 0 ? round($row->amount / $totals['amount'] * 100) : 0; @endphp
                        <tr class="hover:bg-mango-50/40 transition cursor-pointer"
                            @click="$dispatch('open-store-orders', { url: '{{ route('portal.supplier.sales.store_orders', ['store' => $row->id, 'period' => $period]) }}' })">
                            <td class="px-6 py-3.5 text-neutral-400 font-bold">{{ $i + 1 }}</td>
                            <td class="px-6 py-3.5"><span class="font-bold text-neutral-900">{{ $row->name }}</span> <span class="text-xs text-neutral-400">{{ $row->region }}</span></td>
                            <td class="px-6 py-3.5 text-right text-neutral-500">{{ number_format($row->items) }}건</td>
                            <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ number_format($row->qty) }}</td>
                            <td class="px-6 py-3.5 text-right font-black text-mango-700">{{ number_format($row->amount) }}원</td>
                            <td class="px-6 py-3.5 hidden lg:table-cell">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-1.5 rounded-full bg-neutral-100 overflow-hidden"><div class="h-full bg-mango-500" style="width: {{ $pct }}%"></div></div>
                                    <span class="text-xs text-neutral-400 w-8 text-right">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-neutral-50 font-black">
                        <td class="px-6 py-4" colspan="4">합계</td>
                        <td class="px-6 py-4 text-right text-mango-700">{{ number_format($totals['amount']) }}원</td>
                        <td class="hidden lg:table-cell"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
@endsection
