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
@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $orders->map(fn ($o) => [
        'order_no' => $o->order_no,
        'items_count' => (int) $o->items_count,
        'store_amount' => (int) $o->store_amount,
        'status' => $o->status,
        'status_label' => $o->status_label,
        'created_at' => $o->created_at->format('Y.m.d H:i'),
        'show_url' => route('portal.store.orders.show', $o),
    ])->values();
@endphp

<x-wwgrid-tabs gid="purchasesGrid">
    <x-wms.panel title="주문별 총액">
        <div id="purchasesGrid"></div>
    </x-wms.panel>
    <div class="mt-3 rounded-2xl bg-neutral-50 border border-neutral-100 px-6 py-4 flex items-center justify-between font-black text-neutral-900">
        <span>합계 (현재 페이지 제외, 전체 기준)</span>
        <span class="text-mango-700">{{ number_format($totals['amount']) }}원</span>
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
</x-wwgrid-tabs>

@push('scripts')
<script>
(function () {
    const STATUS_CLS = {
        pending: 'bg-neutral-100 text-neutral-600', processing: 'bg-amber-100 text-amber-700',
        shipping: 'bg-sky-100 text-sky-700', completed: 'bg-emerald-100 text-emerald-700',
        canceled: 'bg-rose-100 text-rose-600',
    };
    const grid = ww.grid('purchasesGrid', [
        { header: '주문번호', name: 'order_no', width: 160 },
        { header: '품목수', name: 'items_count', width: 90, align: 'right' },
        { header: '주문 총액', name: 'store_amount', width: 140, align: 'right', renderer: (v) => ww.won(v) },
        { header: '상태', name: 'status', width: 120,
          renderer: (v, row) => ww.badge(row.status_label, STATUS_CLS[v] || STATUS_CLS.pending) },
        { header: '발주일', name: 'created_at', width: 150 },
    ], @json($gridRows));

    ww.bindRowDetail('purchasesGrid', grid, 'show_url', 'order_no');
})();
</script>
@endpush
@endsection
