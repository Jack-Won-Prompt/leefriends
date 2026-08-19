@extends('portal.layout')
@section('title', '매장 발주 주문')

@section('content')
<x-wms.page-head title="매장 발주 주문" subtitle="매장이 접수한 구매주문을 조회합니다" icon="📦" />

<x-wms.filter :action="route('portal.hq.orders.index')" cols="grid-cols-2 md:grid-cols-4">
    <x-slot:actions>
        <a href="{{ route('portal.hq.orders.index') }}" class="inline-flex items-center gap-1 rounded-lg bg-white border border-neutral-200 px-3 py-1.5 text-xs font-bold text-neutral-500 hover:bg-neutral-100">새로고침</a>
    </x-slot:actions>
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
    <x-wms.field label="세금계산서">
        <select name="tax" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="all" @selected($tax === 'all')>전체</option>
            <option value="issued" @selected($tax === 'issued')>발행완료</option>
            <option value="pending" @selected($tax === 'pending')>미발행</option>
        </select>
    </x-wms.field>
    <x-wms.field label="접수일 기간">
        <div class="flex items-center gap-1.5">
            <input type="date" name="from" value="{{ $from }}" class="w-full min-w-0 rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <span class="text-neutral-400 shrink-0">~</span>
            <input type="date" name="to" value="{{ $to }}" class="w-full min-w-0 rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
        </div>
    </x-wms.field>
</x-wms.filter>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $orders->map(fn ($o) => [
        'order_no' => $o->order_no,
        'store_name' => $o->store->name ?? '-',
        'items_count' => (int) $o->items_count,
        'store_amount' => (int) $o->store_amount,
        'supply_amount' => (int) $o->supply_amount,
        'status' => $o->status,
        'status_label' => $o->status_label,
        'tax_issued' => (bool) $o->tax_invoice_id,
        'pay_state' => $o->status === 'canceled' ? 'canceled' : ($o->paid_at ? 'paid' : 'due'),
        'pay_url' => route('portal.hq.orders.payment_request', $o),
        'pay_phone' => (bool) ($o->store?->phone),
        'pay_confirm' => ($o->store->name ?? '매장').'('.($o->store->phone ?? '번호없음').')에 입금요청 SMS를 전송합니다.\n발주금액 '.number_format($o->order_total).'원\n진행하시겠습니까?',
        'created_at' => $o->created_at->format('Y.m.d H:i'),
        'show_url' => route('portal.hq.orders.show', $o),
    ])->values();
@endphp

<x-wwgrid-tabs gid="hqOrdersGrid" :count="$orders->total()">
    <x-wms.panel>
        <div id="hqOrdersGrid"></div>
    </x-wms.panel>
    <div class="mt-5">{{ $orders->links() }}</div>
</x-wwgrid-tabs>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const STATUS_CLS = {
        pending: 'bg-neutral-100 text-neutral-600', processing: 'bg-amber-100 text-amber-700',
        shipping: 'bg-sky-100 text-sky-700', completed: 'bg-emerald-100 text-emerald-700',
        canceled: 'bg-rose-100 text-rose-600',
    };
    const grid = ww.grid('hqOrdersGrid', [
        { header: '주문번호', name: 'order_no', width: 150 },
        { header: '매장', name: 'store_name', width: 130 },
        { header: '품목', name: 'items_count', width: 70, align: 'right' },
        { header: '출고가', name: 'store_amount', width: 110, align: 'right', renderer: (v) => ww.won(v) },
        { header: '공급가(원가)', name: 'supply_amount', width: 120, align: 'right', renderer: (v) => ww.won(v) },
        { header: '상태', name: 'status', width: 100, align: 'center',
          renderer: (v, row) => ww.badge(row.status_label, STATUS_CLS[v] || STATUS_CLS.pending) },
        { header: '세금계산서', name: 'tax_issued', width: 110, align: 'center',
          renderer: (v) => v ? ww.badge('발행완료', 'bg-emerald-100 text-emerald-700') : ww.badge('미발행', 'bg-neutral-100 text-neutral-400') },
        { header: '입금요청', name: 'pay_state', width: 130, align: 'center', sortable: false, exportable: false,
          renderer: (v, row) => {
              if (v === 'canceled') return ww.dash();
              if (v === 'paid') return ww.badge('입금완료', 'bg-emerald-100 text-emerald-700');
              const form = document.createElement('form');
              form.method = 'POST'; form.action = row.pay_url;
              form.addEventListener('submit', (e) => { if (!confirm(row.pay_confirm)) e.preventDefault(); });
              const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
              const b = document.createElement('button');
              b.type = 'submit'; b.textContent = '💬 입금요청';
              b.className = 'inline-flex items-center gap-1 rounded-lg bg-mango-500 hover:bg-mango-600 disabled:opacity-40 text-white font-bold px-3 py-1.5 text-xs transition';
              if (!row.pay_phone) b.disabled = true;
              form.appendChild(b);
              return form;
          } },
        { header: '접수일', name: 'created_at', width: 150 },
    ], @json($gridRows));

    ww.bindRowDetail('hqOrdersGrid', grid, 'show_url', 'order_no');
})();
</script>
@endpush
@endsection
