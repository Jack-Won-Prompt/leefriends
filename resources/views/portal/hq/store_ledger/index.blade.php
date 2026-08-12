@extends('portal.layout')
@section('title', '매장 원장(정산)')

@section('content')
<x-wms.page-head title="매장 원장(정산)" subtitle="매장별 예치금 잔액·미수금을 관리합니다. 발주는 차감, 입금은 충전됩니다." icon="📒">
    <x-slot:actions>
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ $q }}" placeholder="매장 검색" class="rounded-xl border-neutral-200 text-sm py-2">
            <button class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-3.5 py-2 text-sm">검색</button>
        </form>
    </x-slot:actions>
</x-wms.page-head>

<div class="grid sm:grid-cols-2 gap-4 mb-6">
    <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white p-6">
        <p class="text-white/80 font-semibold text-sm">예치금 잔액 합계</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totals['prepaid']) }}<span class="text-lg">원</span></p>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-rose-500 to-rose-600 text-white p-6">
        <p class="text-white/80 font-semibold text-sm">미수금 합계</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totals['unpaid']) }}<span class="text-lg">원</span></p>
    </div>
</div>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $stores->map(fn ($s) => [
        'name' => $s->name,
        'settlement_type' => $s->settlement_type,
        'settlement_label' => $s->settlement_label,
        'virtual_account' => $s->virtual_account ?: '-',
        'ledger_balance' => (int) $s->ledger_balance,
        'show_url' => route('portal.hq.store_ledger.show', $s),
    ])->values();
@endphp

<x-wwgrid-tabs gid="hqStoreLedgerGrid">
    <x-wms.panel>
        <div id="hqStoreLedgerGrid"></div>
    </x-wms.panel>
    <div class="mt-5">{{ $stores->links() }}</div>
</x-wwgrid-tabs>

@push('scripts')
<script>
(function () {
    const grid = ww.grid('hqStoreLedgerGrid', [
        { header: '매장', name: 'name', width: 200 },
        { header: '정산방식', name: 'settlement_type', width: 120, align: 'center',
          renderer: (v, row) => ww.badge(row.settlement_label, v === 'prepaid' ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-500') },
        { header: '가상계좌', name: 'virtual_account', width: 180 },
        { header: '잔액', name: 'ledger_balance', width: 150, align: 'right',
          renderer: (v) => {
              const neg = v < 0;
              const box = document.createElement('div');
              box.className = 'font-black whitespace-nowrap ' + (neg ? 'text-rose-600' : 'text-emerald-600');
              box.appendChild(document.createTextNode(ww.won(v)));
              const sub = document.createElement('span');
              sub.className = 'block text-[11px] font-bold ' + (neg ? 'text-rose-400' : 'text-emerald-400');
              sub.textContent = neg ? '미수' : '예치';
              box.appendChild(sub);
              return box;
          } },
        { header: '관리', name: 'show_url', width: 100, align: 'right', sortable: false, exportable: false,
          renderer: (v) => {
              const a = document.createElement('a');
              a.href = v; a.textContent = '원장 보기';
              a.className = 'text-xs font-bold text-mango-600 hover:text-mango-700';
              return a;
          } },
    ], @json($gridRows));

    ww.bindRowDetail('hqStoreLedgerGrid', grid, 'show_url', 'name');
})();
</script>
@endpush
@endsection
