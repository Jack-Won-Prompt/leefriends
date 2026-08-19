@extends('portal.layout')
@section('title', '발주 내역')

@section('content')
<x-wms.page-head title="발주 내역" subtitle="우리 매장의 발주 내역을 조회합니다" icon="📦">
    <x-slot:actions>
        <a href="{{ route('portal.store.orders.create') }}" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">🛒 재료 발주하기</a>
    </x-slot:actions>
</x-wms.page-head>

@if ($store && $store->settlement_type === 'prepaid')
    <div class="mb-5 flex items-center justify-between rounded-2xl p-5 text-white {{ $store->ledger_balance < 0 ? 'bg-gradient-to-br from-rose-500 to-rose-600' : 'bg-gradient-to-br from-emerald-500 to-emerald-600' }}">
        <div>
            <p class="text-white/80 font-semibold text-sm">{{ $store->ledger_balance < 0 ? '미수금 (예치 잔액 부족)' : '예치금 잔액' }}</p>
            <p class="text-2xl font-black mt-0.5">{{ number_format(abs($store->ledger_balance)) }}<span class="text-base">원</span></p>
        </div>
        <p class="text-xs text-white/80 text-right leading-relaxed">발주 시 예치금에서<br>자동 차감됩니다</p>
    </div>
@endif

<x-date-filter :from="$from" :to="$to" label="발주일 기간" />

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $orders->map(fn ($o) => [
        'order_no' => $o->order_no,
        'items_count' => (int) $o->items_count,
        'store_amount' => (int) $o->store_amount,
        'status' => $o->status,
        'status_label' => $o->status_label,
        'is_sample' => $o->isSample(),
        'paid' => (bool) $o->paid_at,
        'created_at' => $o->created_at->format('Y.m.d H:i'),
        'show_url' => route('portal.store.orders.show', $o),
    ])->values();
@endphp

<x-wwgrid-tabs gid="storeOrdersGrid" :count="$orders->total()">
    <x-wms.panel>
        <div id="storeOrdersGrid"></div>
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
    const grid = ww.grid('storeOrdersGrid', [
        { header: '주문번호', name: 'order_no', width: 160 },
        { header: '품목수', name: 'items_count', width: 90, align: 'right' },
        { header: '결제금액', name: 'store_amount', width: 130, align: 'right', renderer: (v) => ww.won(v) },
        { header: '상태', name: 'status', width: 200, sortable: false,
          renderer: (v, row) => {
              const wrap = ww.el('div', 'flex items-center gap-1.5');
              wrap.appendChild(ww.badge(row.status_label, STATUS_CLS[v] || STATUS_CLS.pending));
              if (!row.is_sample) {
                  if (row.paid) wrap.appendChild(ww.badge('💰 입금완료', 'bg-emerald-100 text-emerald-700'));
                  else if (v !== 'canceled') wrap.appendChild(ww.badge('입금대기', 'bg-neutral-100 text-neutral-400'));
              }
              return wrap;
          } },
        { header: '발주일', name: 'created_at', width: 150 },
    ], @json($gridRows));

    ww.bindRowDetail('storeOrdersGrid', grid, 'show_url', 'order_no');
})();
</script>
@endpush
@endsection
