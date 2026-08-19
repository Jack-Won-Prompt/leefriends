@extends('portal.layout')
@section('title', '매장별 입금현황')

@section('content')
<x-wms.page-head title="매장별 입금현황" subtitle="매장별 총 발주액 대비 입금완료·미입금 집계 (계좌 대사 기준)" icon="💳">
    <x-slot:actions>
        <a href="{{ route('portal.hq.store_payments.excel_all', ['period' => $period, 'from' => $from, 'to' => $to, 'year' => $year, 'month' => $month]) }}"
           id="btnStorePayExcel"
           class="inline-flex items-center gap-1 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 text-sm">⬇️ 엑셀 (선택 매장 / 전체)</a>
    </x-slot:actions>
</x-wms.page-head>

{{-- 전체 + 월별(1~12월) + 기간 조회 --}}
@php
    $btn = 'inline-flex items-center justify-center rounded-xl px-3.5 py-2 text-sm font-bold transition';
    $isRange = ! $month && ($from || $to);
@endphp
<div class="flex flex-wrap items-end justify-between gap-4 mb-3 rounded-2xl bg-white shadow-sm border border-neutral-100 p-4">
    <div class="flex flex-wrap items-center gap-1.5">
        <a href="{{ route('portal.hq.store_payments.index') }}"
           class="{{ $btn }} {{ ! $month && ! $isRange ? 'bg-mango-500 text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">전체</a>
        <span class="mx-1 text-neutral-300">|</span>
        <span class="text-sm font-bold text-neutral-500 mr-1">{{ $year }}년</span>
        @for ($m = 1; $m <= 12; $m++)
            <a href="{{ route('portal.hq.store_payments.index', ['year' => $year, 'month' => $m]) }}"
               class="{{ $btn }} {{ (int) $month === $m ? 'bg-mango-500 text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">{{ $m }}월</a>
        @endfor
    </div>
    <form method="GET" action="{{ route('portal.hq.store_payments.index') }}"
          class="flex items-end gap-2 {{ $isRange ? 'ring-2 ring-mango-300 rounded-xl p-2' : '' }}">
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">시작일</label>
            <input type="date" name="from" value="{{ $isRange ? $from : '' }}" class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">종료일</label>
            <input type="date" name="to" value="{{ $isRange ? $to : '' }}" class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <button type="submit" class="rounded-xl bg-neutral-800 hover:bg-neutral-900 text-white font-bold px-4 py-2 text-sm transition">기간 조회</button>
    </form>
</div>

{{-- 요약 --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="rounded-2xl bg-neutral-900 text-white p-6">
        <p class="text-white/70 font-semibold text-sm">총 발주액</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totals['total']) }}<span class="text-lg">원</span></p>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white p-6">
        <p class="text-white/80 font-semibold text-sm">입금완료</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totals['paid']) }}<span class="text-lg">원</span></p>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-amber-400 to-orange-500 text-white p-6">
        <p class="text-white/80 font-semibold text-sm">미입금</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totals['unpaid']) }}<span class="text-lg">원</span></p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-neutral-100">
        <p class="text-sm text-neutral-500 font-medium">미입금 발주</p>
        <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($totals['unpaid_cnt']) }}<span class="text-lg">건</span></p>
    </div>
</div>

@include('portal.partials.wwgrid-assets')
@php
    $qp = ['period' => $period, 'from' => $from, 'to' => $to, 'year' => $year, 'month' => $month];
    $gridRows = $byStore->map(function ($s) use ($qp) {
        $unpaidAmt = (int) $s->total - (int) $s->paid;
        return [
            'id' => (int) $s->id,
            'name' => $s->name,
            'region' => $s->region,
            'cnt' => (int) $s->cnt,
            'total' => (int) $s->total,
            'paid' => (int) $s->paid,
            'unpaid_amt' => $unpaidAmt,
            'unpaid_cnt' => (int) $s->unpaid_cnt,
            'last_paid_at' => $s->last_paid_at ? \Illuminate\Support\Carbon::parse($s->last_paid_at)->format('Y.m.d') : '-',
            'show_url' => route('portal.hq.store_payments.show', array_merge(['store' => $s->id], $qp)),
            'req_url' => route('portal.hq.store_payments.request_unpaid', array_merge(['store' => $s->id], $qp)),
        ];
    })->values();
@endphp

<x-wwgrid-tabs gid="hqStorePaymentsGrid">
    <x-wms.panel title="매장별 입금현황">
        <div id="hqStorePaymentsGrid"></div>
    </x-wms.panel>
</x-wwgrid-tabs>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const grid = ww.grid('hqStorePaymentsGrid', [
        { header: '매장', name: 'name', width: 180,
          renderer: (v, row) => {
              const box = document.createElement('div');
              box.className = 'font-bold text-neutral-900';
              box.appendChild(document.createTextNode(v));
              const sub = document.createElement('span');
              sub.className = 'block text-xs font-normal text-neutral-400';
              sub.textContent = row.region || '';
              box.appendChild(sub);
              return box;
          } },
        { header: '발주', name: 'cnt', width: 80, align: 'right', renderer: (v) => ww.num(v) },
        { header: '총 발주액', name: 'total', width: 130, align: 'right', renderer: (v) => ww.num(v) },
        { header: '입금완료', name: 'paid', width: 130, align: 'right',
          renderer: (v) => ww.el('span', 'text-emerald-600 font-semibold', ww.num(v)) },
        { header: '미입금', name: 'unpaid_amt', width: 130, align: 'right',
          renderer: (v) => ww.el('span', v > 0 ? 'text-amber-600 font-bold' : 'text-neutral-400', ww.num(v)) },
        { header: '미입금 건', name: 'unpaid_cnt', width: 100, align: 'right',
          renderer: (v) => v > 0
              ? ww.badge(String(v), 'bg-amber-100 text-amber-700')
              : ww.el('span', 'text-emerald-600 text-xs font-bold', '완납') },
        { header: '최근입금', name: 'last_paid_at', width: 110 },
        { header: '', name: 'req_url', width: 140, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              if (row.unpaid_cnt <= 0) return ww.el('span', 'text-neutral-300', '›');
              const form = document.createElement('form');
              form.method = 'POST'; form.action = v;
              const conf = row.name + '에 미입금 ' + row.unpaid_cnt + '건 · ' + ww.num(row.unpaid_amt) + '원 안내 SMS를 전송합니다.\n진행하시겠습니까?';
              form.addEventListener('submit', (e) => { if (!confirm(conf)) e.preventDefault(); });
              const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
              const b = document.createElement('button');
              b.type = 'submit'; b.textContent = '💬 미입금 SMS';
              b.className = 'inline-flex items-center gap-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold px-3 py-1.5 text-xs transition';
              form.appendChild(b);
              return form;
          } },
    ], @json($gridRows));

    ww.bindRowDetail('hqStorePaymentsGrid', grid, 'show_url', 'name');

    // 엑셀: 체크된 매장이 있으면 해당 매장만, 없으면 조회된 전체 매장 다운로드
    const excelBtn = document.getElementById('btnStorePayExcel');
    if (excelBtn) excelBtn.addEventListener('click', function (e) {
        e.preventDefault();
        let url = excelBtn.getAttribute('href');
        const rows = grid.getCheckedRows();
        if (rows.length) {
            const qs = rows.map((r) => 'stores[]=' + encodeURIComponent(r.id)).join('&');
            url += (url.indexOf('?') === -1 ? '?' : '&') + qs;
        }
        window.location = url;
    });
})();
</script>
@endpush
@endsection
