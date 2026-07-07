@extends('portal.layout')
@section('title', '공급사 발주 현황')

@php
    $stBadge = [
        'created' => 'bg-amber-100 text-amber-700',
        'confirmed' => 'bg-sky-100 text-sky-700',
        'shipped' => 'bg-indigo-100 text-indigo-700',
        'received' => 'bg-emerald-100 text-emerald-700',
        'canceled' => 'bg-neutral-100 text-neutral-400',
    ];
@endphp

@section('content')
<x-wms.page-head title="공급사 발주 현황" subtitle="매장 발주 중 공급처 직배송분(공급사별 판매주문)을 한눈에 확인합니다." icon="🏭" />

{{-- 요약 --}}
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <div class="rounded-2xl bg-gradient-to-br from-sky-500 to-sky-600 text-white p-6">
        <p class="text-white/80 font-semibold text-sm">공급사 공급액 합계</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totalSupply) }}<span class="text-lg">원</span></p>
        <p class="text-white/70 text-xs mt-1">필터 기준 · 본사 매입</p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-neutral-100">
        <p class="text-sm text-neutral-500 font-medium">판매주문 건수</p>
        <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($salesOrders->total()) }}<span class="text-lg">건</span></p>
    </div>
</div>

{{-- 필터 --}}
<form method="GET" class="flex flex-wrap items-center gap-2 mb-4">
    <select name="supplier" onchange="this.form.submit()" class="rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm py-2">
        <option value="all">전체 공급사</option>
        @foreach ($suppliers as $s)
            <option value="{{ $s->id }}" @selected((string) $supplierId === (string) $s->id)>{{ $s->name }}</option>
        @endforeach
    </select>
    <input type="hidden" name="status" value="{{ $status }}">
    <input type="date" name="from" value="{{ $from }}" class="rounded-xl border-neutral-200 text-sm py-2">
    <span class="text-neutral-400">~</span>
    <input type="date" name="to" value="{{ $to }}" class="rounded-xl border-neutral-200 text-sm py-2">
    <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm">조회</button>
    <div class="flex flex-wrap gap-1.5">
        <a href="{{ request()->fullUrlWithQuery(['status' => 'all']) }}"
           class="px-3.5 py-2 rounded-full text-sm font-bold {{ $status === 'all' ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 border border-neutral-200 hover:bg-mango-50' }}">전체</a>
        @foreach ($statuses as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['status' => $key]) }}"
               class="px-3.5 py-2 rounded-full text-sm font-bold {{ $status === $key ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 border border-neutral-200 hover:bg-mango-50' }}">{{ $label }}</a>
        @endforeach
    </div>
</form>

<x-wms.panel>
    @if ($salesOrders->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">해당하는 공급사 발주가 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">발주일</th>
                        <th class="text-left font-semibold px-6 py-3">판매주문</th>
                        <th class="text-left font-semibold px-6 py-3">공급사</th>
                        <th class="text-left font-semibold px-6 py-3">매장</th>
                        <th class="text-right font-semibold px-6 py-3">품목</th>
                        <th class="text-right font-semibold px-6 py-3">공급액</th>
                        <th class="text-center font-semibold px-6 py-3">상태</th>
                        <th class="text-right font-semibold px-6 py-3 w-20">상세</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($salesOrders as $so)
                        <tr class="hover:bg-mango-50/40 transition">
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400 whitespace-nowrap">{{ $so->created_at->format('Y.m.d') }}</td>
                            <td class="px-6 py-3.5 font-mono font-bold text-neutral-700">{{ $so->sales_order_no }}</td>
                            <td class="px-6 py-3.5"><span class="font-bold text-neutral-900">{{ optional($so->supplier)->name ?? '공급처' }}</span></td>
                            <td class="px-6 py-3.5 text-neutral-600">{{ optional($so->store)->name ?? '-' }}</td>
                            <td class="px-6 py-3.5 text-right text-neutral-500">{{ number_format($so->item_count) }}건</td>
                            <td class="px-6 py-3.5 text-right font-black text-mango-700">{{ number_format($so->supply_amount) }}원</td>
                            <td class="px-6 py-3.5 text-center">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $stBadge[$so->status] ?? 'bg-neutral-100 text-neutral-600' }}">{{ $so->status_label }}</span>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                @if ($so->order)
                                    <a href="{{ route('portal.hq.orders.show', $so->order) }}" class="text-xs font-bold text-mango-600 hover:text-mango-700">발주</a>
                                @else - @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-wms.panel>

@if ($salesOrders->hasPages())
    <div class="mt-5">{{ $salesOrders->links() }}</div>
@endif
@endsection
