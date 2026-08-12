@extends('portal.layout')
@section('title', '주문 관리')

@section('content')
<x-wms.page-head title="주문 관리" subtitle="자사 품목이 포함된 매장 주문을 조회합니다" icon="📦" />

<x-wms.filter :action="route('portal.supplier.orders.index')">
    <x-wms.field label="매장(배송지)">
        <select name="store" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="all">전체 매장</option>
            @foreach ($stores as $s)
                <option value="{{ $s->id }}" @selected((string) $store === (string) $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </x-wms.field>
    <x-wms.field label="발주 시작일">
        <input type="date" name="from" value="{{ $from ?? '' }}" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
    </x-wms.field>
    <x-wms.field label="발주 종료일">
        <input type="date" name="to" value="{{ $to ?? '' }}" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
    </x-wms.field>
</x-wms.filter>

<x-wms.toolbar :count="$orders->total()" />

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $orders->map(function ($o) {
        $items = $o->items;
        $total = $items->count();
        $delivered = $items->where('fulfillment_status', 'delivered')->count();
        $fStat = $delivered === $total ? 'delivered' : ($items->whereIn('fulfillment_status', ['shipping','delivered'])->count() > 0 ? 'shipping' : 'pending');
        $fLabel = $delivered === $total ? '배송완료' : ($fStat === 'shipping' ? "진행중 ($delivered/$total)" : '대기');

        return [
            'order_no' => $o->order_no,
            'store_name' => $o->store->name ?? '-',
            'store_address' => $o->store->address ?? '-',
            'items_count' => (int) $total,
            'supply_amount' => (int) $items->sum('supply_line_amount'),
            'f_status' => $fStat,
            'f_label' => $fLabel,
            'created_at' => $o->created_at->format('Y.m.d H:i'),
            'show_url' => route('portal.supplier.orders.show', $o),
        ];
    })->values();
@endphp

<x-wwgrid-tabs gid="supplierOrdersGrid">
    <x-wms.panel>
        <div id="supplierOrdersGrid"></div>
    </x-wms.panel>
    <div class="mt-5">{{ $orders->links() }}</div>
</x-wwgrid-tabs>

@push('scripts')
<script>
(function () {
    const FSTAT_CLS = {
        pending: 'bg-neutral-100 text-neutral-600',
        shipping: 'bg-sky-100 text-sky-700',
        delivered: 'bg-emerald-100 text-emerald-700',
    };
    const grid = ww.grid('supplierOrdersGrid', [
        { header: '주문번호', name: 'order_no', width: 150 },
        { header: '매장', name: 'store_name', width: 130 },
        { header: '배송지 주소', name: 'store_address', width: 240 },
        { header: '자사 품목', name: 'items_count', width: 90, align: 'right', renderer: (v) => ww.num(v) + '개' },
        { header: '공급액', name: 'supply_amount', width: 120, align: 'right', renderer: (v) => ww.won(v) },
        { header: '배송현황', name: 'f_status', width: 110, align: 'center',
          renderer: (v, row) => ww.badge(row.f_label, FSTAT_CLS[v] || FSTAT_CLS.pending) },
        { header: '접수일', name: 'created_at', width: 150 },
    ], @json($gridRows));

    ww.bindRowDetail('supplierOrdersGrid', grid, 'show_url', 'order_no');
})();
</script>
@endpush
@endsection
