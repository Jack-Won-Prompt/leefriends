@extends('portal.layout')
@section('title', '매출 현황')

@section('content')
@include('portal.partials.store-orders-modal')
<x-wms.page-head title="매출 현황" subtitle="총 판매액 · 매장별 매출 (행 클릭 시 발주 상세)" icon="📈" />
@include('portal.partials.period-tabs', ['routeName' => 'portal.hq.sales', 'period' => $period])

{{-- 요약 --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="rounded-2xl bg-gradient-to-br from-mango-500 to-mango-600 text-white p-6">
        <p class="text-white/80 font-semibold text-sm">총 판매액</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totals['sales']) }}<span class="text-lg">원</span></p>
        <p class="text-white/70 text-xs mt-1">발주 {{ number_format($totals['orders']) }}건</p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-neutral-100">
        <p class="text-sm text-neutral-500 font-medium">총 공급원가</p>
        <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($totals['cost']) }}<span class="text-lg">원</span></p>
    </div>
    <div class="rounded-2xl bg-neutral-900 text-white p-6">
        <p class="text-white/70 font-semibold text-sm">본사 마진</p>
        <p class="text-3xl font-black text-mango-300 mt-1">{{ number_format($totals['margin']) }}<span class="text-lg">원</span></p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-neutral-100">
        <p class="text-sm text-neutral-500 font-medium">발주 건수</p>
        <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($totals['orders']) }}<span class="text-lg">건</span></p>
    </div>
</div>

{{-- 매장별 매출 (x-data: 행 클릭 $dispatch 스코프) --}}
<div x-data class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">매장별 매출</div>
    @if ($byStore->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">집계할 매출이 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3 w-10">#</th>
                        <th class="text-left font-semibold px-6 py-3">매장</th>
                        <th class="text-right font-semibold px-6 py-3">발주</th>
                        <th class="text-right font-semibold px-6 py-3">판매액</th>
                        <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">원가</th>
                        <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">마진</th>
                        <th class="text-right font-semibold px-6 py-3 hidden lg:table-cell w-32">비중</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($byStore as $i => $row)
                        @php $pct = $totals['sales'] > 0 ? round($row->sales / $totals['sales'] * 100) : 0; @endphp
                        <tr class="hover:bg-mango-50/40 transition cursor-pointer"
                            @click="$dispatch('open-store-orders', { url: '{{ route('portal.hq.sales.store_orders', ['store' => $row->id, 'period' => $period]) }}' })">
                            <td class="px-6 py-3.5 text-neutral-400 font-bold">{{ $i + 1 }}</td>
                            <td class="px-6 py-3.5"><span class="font-bold text-neutral-900">{{ $row->name }}</span> <span class="text-xs text-neutral-400">{{ $row->region }}</span></td>
                            <td class="px-6 py-3.5 text-right text-neutral-500">{{ number_format($row->cnt) }}건</td>
                            <td class="px-6 py-3.5 text-right font-black text-mango-700">{{ number_format($row->sales) }}원</td>
                            <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ number_format($row->cost) }}원</td>
                            <td class="px-6 py-3.5 text-right hidden md:table-cell text-emerald-600 font-semibold">{{ number_format($row->sales - $row->cost) }}원</td>
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
                        <td class="px-6 py-4" colspan="3">합계</td>
                        <td class="px-6 py-4 text-right text-mango-700">{{ number_format($totals['sales']) }}원</td>
                        <td class="px-6 py-4 text-right hidden md:table-cell text-neutral-600">{{ number_format($totals['cost']) }}원</td>
                        <td class="px-6 py-4 text-right hidden md:table-cell text-emerald-600">{{ number_format($totals['margin']) }}원</td>
                        <td class="hidden lg:table-cell"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
@endsection
