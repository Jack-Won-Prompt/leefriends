@extends('portal.layout')
@section('title', '출고 관리')

@section('content')
<x-wms.page-head title="출고 관리" subtitle="매장별 출고 생성·송장 입력·출고확정" icon="🚚">
    <x-slot:actions>
        <a href="{{ route($routePrefix . '.shipments.create') }}" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">+ 출고 생성</a>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.filter :action="route($routePrefix . '.shipments.index')">
    <x-wms.field label="진행상태">
        <select name="status" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="all">전체</option>
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </x-wms.field>
    <x-wms.field label="매장">
        <select name="store" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="all">전체 매장</option>
            @foreach ($stores as $s)
                <option value="{{ $s->id }}" @selected((string) $store === (string) $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </x-wms.field>
</x-wms.filter>

<x-wms.toolbar :count="$shipments->total()" />

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $shipments->map(fn ($s) => [
        'shipment_no' => $s->shipment_no,
        'store_name' => $s->store->name ?? '-',
        'item_count' => (int) $s->item_count,
        'total_qty' => (int) $s->total_qty,
        'carrier' => $s->carrier ?: '-',
        'tracking_no' => $s->tracking_no ?: '-',
        'status' => $s->status,
        'status_label' => $s->status_label,
        'created_at' => $s->created_at->format('Y.m.d H:i'),
        'show_url' => route($routePrefix . '.shipments.show', $s),
    ])->values();
@endphp

<x-wwgrid-tabs gid="shipmentsGrid">
    <x-wms.panel>
        <div id="shipmentsGrid"></div>
    </x-wms.panel>
    <div class="mt-5">{{ $shipments->links() }}</div>
</x-wwgrid-tabs>

@push('scripts')
<script>
(function () {
    const STATUS_CLS = {
        created: 'bg-neutral-100 text-neutral-600', confirmed: 'bg-sky-100 text-sky-700',
        shipped: 'bg-indigo-100 text-indigo-700', delivered: 'bg-teal-100 text-teal-700',
        received: 'bg-emerald-100 text-emerald-700', canceled: 'bg-rose-100 text-rose-600',
    };
    const grid = ww.grid('shipmentsGrid', [
        { header: '출고번호', name: 'shipment_no', width: 150 },
        { header: '매장', name: 'store_name', width: 130 },
        { header: '품목', name: 'item_count', width: 80, align: 'right', renderer: (v) => ww.num(v) },
        { header: '수량', name: 'total_qty', width: 80, align: 'right', renderer: (v) => ww.num(v) },
        { header: '택배사', name: 'carrier', width: 110 },
        { header: '송장번호', name: 'tracking_no', width: 150 },
        { header: '상태', name: 'status', width: 110, align: 'center',
          renderer: (v, row) => ww.badge(row.status_label, STATUS_CLS[v] || 'bg-neutral-100 text-neutral-600') },
        { header: '생성일', name: 'created_at', width: 150 },
    ], @json($gridRows));

    ww.bindRowDetail('shipmentsGrid', grid, 'show_url', 'shipment_no');
})();
</script>
@endpush
@endsection
