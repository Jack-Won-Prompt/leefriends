@extends('portal.layout')
@section('title', '매장 원장(정산)')

@section('content')
<x-wms.page-head title="매장 원장(정산)" subtitle="매장별 예치금 잔액·미수금을 관리합니다. 발주는 차감, 입금은 충전됩니다." icon="📒" />

<x-wms.filter :action="url()->current()" cols="grid-cols-1 md:grid-cols-3">
    <x-wms.field label="매장명">
        <input type="text" name="q" value="{{ $q }}" placeholder="매장명 검색"
               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
    </x-wms.field>
</x-wms.filter>

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
