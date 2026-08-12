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

{{-- 매장별 공급액 --}}
@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $byStore->map(fn ($row) => [
        'name' => $row->name,
        'region' => $row->region,
        'items' => (int) $row->items,
        'qty' => (int) $row->qty,
        'amount' => (int) $row->amount,
        'pct' => $totals['amount'] > 0 ? (int) round($row->amount / $totals['amount'] * 100) : 0,
        'store_orders_url' => route('portal.supplier.sales.store_orders', ['store' => $row->id, 'period' => $period]),
    ])->values();
@endphp

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">매장별 공급액 (직배송)</div>
    <div class="p-4"><div id="supplierSalesGrid"></div></div>
</div>

@push('scripts')
<script>
(function () {
    const grid = ww.grid('supplierSalesGrid', [
        { header: '매장 (배송지)', name: 'name', width: 220,
          renderer: (v, row) => {
              const wrap = ww.el('div');
              wrap.appendChild(ww.el('span', 'font-bold text-neutral-900', v));
              if (row.region) { wrap.appendChild(document.createTextNode(' ')); wrap.appendChild(ww.el('span', 'text-xs text-neutral-400', row.region)); }
              return wrap;
          } },
        { header: '품목', name: 'items', width: 90, align: 'right', renderer: (v) => ww.num(v) + '건' },
        { header: '수량', name: 'qty', width: 90, align: 'right', renderer: (v) => ww.num(v) },
        { header: '공급액', name: 'amount', width: 130, align: 'right', renderer: (v) => ww.won(v) },
        { header: '비중', name: 'pct', width: 140, align: 'right', exportable: false, summary: false,
          renderer: (v) => {
              const wrap = ww.el('div', 'flex items-center gap-2');
              const track = ww.el('div', 'flex-1 h-1.5 rounded-full bg-neutral-100 overflow-hidden');
              const bar = ww.el('div', 'h-full bg-mango-500'); bar.style.width = v + '%';
              track.appendChild(bar);
              wrap.appendChild(track);
              wrap.appendChild(ww.el('span', 'text-xs text-neutral-400 w-8 text-right', v + '%'));
              return wrap;
          } },
    ], @json($gridRows));

    document.getElementById('supplierSalesGrid').addEventListener('click', function (e) {
        if (e.target.closest('a, button, input, select, form')) return;
        const cell = e.target.closest('[data-row-index]');
        if (!cell) return;
        const row = grid.getData()[parseInt(cell.dataset.rowIndex, 10)];
        if (!row) return;
        window.getSelection()?.removeAllRanges();
        window.dispatchEvent(new CustomEvent('open-store-orders', { detail: { url: row.store_orders_url } }));
    });
})();
</script>
@endpush
@endsection
