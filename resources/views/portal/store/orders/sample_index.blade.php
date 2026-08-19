@extends('portal.layout')
@section('title', '샘플 주문 내역')

@section('content')
<x-wms.page-head title="샘플 주문 내역" subtitle="신상품·시식용 샘플 주문 내역을 조회합니다 (무상)" icon="🧪">
    <x-slot:actions>
        <a href="{{ route('portal.store.sample_orders.create') }}" class="inline-flex items-center gap-1 rounded-xl bg-violet-500 hover:bg-violet-600 text-white font-bold px-4 py-2 text-sm transition">🧪 샘플 주문하기</a>
    </x-slot:actions>
</x-wms.page-head>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $orders->map(fn ($o) => [
        'order_no' => $o->order_no,
        'items_count' => (int) $o->items_count,
        'status' => $o->status,
        'status_label' => $o->status_label,
        'created_at' => $o->created_at->format('Y.m.d H:i'),
        'show_url' => route('portal.store.orders.show', $o),
    ])->values();
@endphp

<x-wwgrid-tabs gid="sampleOrdersGrid" :count="$orders->total()">
    <x-wms.panel>
        <div id="sampleOrdersGrid"></div>
    </x-wms.panel>
    <div class="mt-5">{{ $orders->links() }}</div>
</x-wwgrid-tabs>

@push('scripts')
<script>
(function () {
    const STATUS_CLS = {
        pending: 'bg-neutral-100 text-neutral-600', processing: 'bg-amber-100 text-amber-700',
        shipping: 'bg-sky-100 text-sky-700', completed: 'bg-emerald-100 text-emerald-700',
        canceled: 'bg-rose-100 text-rose-600',
    };
    const grid = ww.grid('sampleOrdersGrid', [
        { header: '주문번호', name: 'order_no', width: 160 },
        { header: '품목수', name: 'items_count', width: 90, align: 'right' },
        { header: '구분', name: 'kind', width: 110, sortable: false, exportable: false,
          renderer: () => ww.badge('샘플 · 무상', 'bg-violet-100 text-violet-700') },
        { header: '상태', name: 'status', width: 120,
          renderer: (v, row) => ww.badge(row.status_label, STATUS_CLS[v] || STATUS_CLS.pending) },
        { header: '주문일', name: 'created_at', width: 150 },
    ], @json($gridRows));

    ww.bindRowDetail('sampleOrdersGrid', grid, 'show_url', 'order_no');
})();
</script>
@endpush
@endsection
