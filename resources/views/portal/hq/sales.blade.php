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

@include('portal.partials.wwgrid-assets')
@php
    $totalSales = $totals['sales'];
    $gridRows = $byStore->map(fn ($row) => [
        'name' => $row->name,
        'region' => $row->region,
        'cnt' => (int) $row->cnt,
        'sales' => (int) $row->sales,
        'cost' => (int) $row->cost,
        'margin' => (int) $row->sales - (int) $row->cost,
        'pct' => $totalSales > 0 ? round($row->sales / $totalSales * 100) : 0,
        'store_url' => route('portal.hq.sales.store_orders', ['store' => $row->id, 'period' => $period]),
    ])->values();
@endphp

<x-wms.panel title="매장별 매출">
    <div id="hqSalesGrid"></div>
</x-wms.panel>

@push('scripts')
<script>
(function () {
    const grid = ww.grid('hqSalesGrid', [
        { header: '매장', name: 'name', width: 200,
          renderer: (v, row) => {
              const box = document.createElement('span');
              const nm = document.createElement('span');
              nm.className = 'font-bold text-neutral-900'; nm.textContent = v;
              box.appendChild(nm);
              if (row.region) {
                  const rg = document.createElement('span');
                  rg.className = 'text-xs text-neutral-400 ml-1'; rg.textContent = row.region;
                  box.appendChild(rg);
              }
              return box;
          } },
        { header: '발주', name: 'cnt', width: 90, align: 'right', summary: false, renderer: (v) => ww.num(v) + '건' },
        { header: '판매액', name: 'sales', width: 130, align: 'right',
          renderer: (v) => ww.el('span', 'font-black text-mango-700', ww.won(v)) },
        { header: '원가', name: 'cost', width: 130, align: 'right',
          renderer: (v) => ww.el('span', 'text-neutral-500', ww.won(v)) },
        { header: '마진', name: 'margin', width: 130, align: 'right',
          renderer: (v) => ww.el('span', 'text-emerald-600 font-semibold', ww.won(v)) },
        { header: '비중', name: 'pct', width: 140, summary: false, exportable: false,
          renderer: (v) => {
              const wrap = document.createElement('div');
              wrap.className = 'flex items-center gap-2';
              const track = document.createElement('div');
              track.className = 'flex-1 h-1.5 rounded-full bg-neutral-100 overflow-hidden';
              const bar = document.createElement('div');
              bar.className = 'h-full bg-mango-500'; bar.style.width = v + '%';
              track.appendChild(bar);
              const lbl = document.createElement('span');
              lbl.className = 'text-xs text-neutral-400 w-8 text-right'; lbl.textContent = v + '%';
              wrap.appendChild(track); wrap.appendChild(lbl);
              return wrap;
          } },
    ], @json($gridRows));

    document.getElementById('hqSalesGrid').addEventListener('click', function (e) {
        if (e.target.closest('a, button, input, select, form')) return;
        const cell = e.target.closest('[data-row-index]');
        if (!cell) return;
        const row = grid.getData()[parseInt(cell.dataset.rowIndex, 10)];
        if (!row) return;
        window.getSelection()?.removeAllRanges();
        window.dispatchEvent(new CustomEvent('open-store-orders', { detail: { url: row.store_url } }));
    });
})();
</script>
@endpush
@endsection
