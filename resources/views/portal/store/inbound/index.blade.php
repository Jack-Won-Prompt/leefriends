@extends('portal.layout')
@section('title', '입고예정 · 배송')

@section('content')
<x-wms.page-head title="입고예정 · 배송" subtitle="배송중 출고를 인수·입고하고, 입고예정을 확인합니다" icon="🚚" />

@include('portal.partials.wwgrid-assets')
@php
    $inTransitRows = $inTransit->map(fn ($s) => [
        'shipment_no' => $s->shipment_no,
        'seller_name' => $s->seller_name,
        'carrier' => $s->carrier,
        'tracking_no' => $s->tracking_no,
        'item_count' => (int) $s->item_count,
        'total_qty' => (int) $s->total_qty,
        'confirmed_at' => $s->confirmed_at?->format('Y.m.d H:i') ?? '',
        'show_url' => route('portal.store.shipments.show', $s),
    ])->values();
    $expectedRows = $expected->map(fn ($so) => [
        'sales_order_no' => $so->sales_order_no,
        'order_no' => $so->order->order_no ?? '',
        'seller_name' => $so->seller_name,
        'item_count' => (int) $so->item_count,
        'confirmed_at' => $so->confirmed_at?->format('Y.m.d H:i') ?? '',
    ])->values();
@endphp

{{-- 배송중 (출고확정, 송장 있음) --}}
<h2 class="text-base font-extrabold text-neutral-900 mb-3">🚚 배송중 (입고 대기)</h2>
<x-wms.panel class="mb-8">
    <div id="inTransitGrid"></div>
</x-wms.panel>

{{-- 입고예정 (판매주문 확인됨, 출고 전) --}}
<h2 class="text-lg font-extrabold text-neutral-900 mb-3">📋 입고예정 (출고 대기)</h2>
<x-wms.panel>
    <div id="expectedGrid"></div>
</x-wms.panel>

@push('scripts')
<script>
(function () {
    ww.grid('inTransitGrid', [
        { header: '출고번호', name: 'shipment_no', width: 150 },
        { header: '공급', name: 'seller_name', width: 140 },
        { header: '송장', name: 'tracking_no', width: 190, sortable: false,
          renderer: (v, row) => {
              const wrap = ww.el('div');
              if (row.carrier) wrap.appendChild(ww.el('span', 'text-neutral-700', row.carrier + ' '));
              wrap.appendChild(ww.el('span', 'font-bold text-mango-700', row.tracking_no || ''));
              return wrap;
          } },
        { header: '품목/수량', name: 'item_count', width: 130, align: 'right', sortable: false,
          renderer: (v, row) => row.item_count + '건 / ' + ww.num(row.total_qty) },
        { header: '출고일', name: 'confirmed_at', width: 150 },
        { header: '처리', name: 'action', width: 120, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              const a = ww.el('a', 'rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white px-3 py-1.5 font-semibold inline-block', '입고 처리');
              a.href = row.show_url;
              return a;
          } },
    ], @json($inTransitRows));

    ww.grid('expectedGrid', [
        { header: '판매주문', name: 'sales_order_no', width: 170, sortable: false,
          renderer: (v, row) => {
              const wrap = ww.el('div');
              wrap.appendChild(ww.el('span', 'font-bold text-neutral-900', row.sales_order_no || ''));
              wrap.appendChild(ww.el('span', 'block text-xs text-neutral-400', row.order_no || ''));
              return wrap;
          } },
        { header: '공급', name: 'seller_name', width: 140 },
        { header: '품목', name: 'item_count', width: 90, align: 'right' },
        { header: '상태', name: 'status', width: 110, align: 'center', sortable: false, exportable: false,
          renderer: () => ww.badge('입고예정', 'bg-sky-100 text-sky-700') },
        { header: '확인일', name: 'confirmed_at', width: 150 },
    ], @json($expectedRows));
})();
</script>
@endpush
@endsection
