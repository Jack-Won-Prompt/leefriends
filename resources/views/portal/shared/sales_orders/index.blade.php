@extends('portal.layout')
@section('title', '판매주문')

@section('content')
<div x-data="{ open: null }" @so-open.window="open = $event.detail">
<x-wms.page-head title="판매주문" subtitle="구매주문에서 분할된 판매주문을 확인·처리합니다" icon="🧾" />

<x-wms.filter :action="route($routePrefix . '.sales_orders.index')">
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
    <x-wms.field label="접수 시작일">
        <input type="date" name="from" value="{{ $from ?? '' }}" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
    </x-wms.field>
    <x-wms.field label="접수 종료일">
        <input type="date" name="to" value="{{ $to ?? '' }}" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
    </x-wms.field>
</x-wms.filter>

@include('portal.partials.wwgrid-assets')
@php
    $lifecycleCls = [
        'created' => 'bg-neutral-100 text-neutral-600',
        'confirmed' => 'bg-sky-100 text-sky-700',
        'shipped' => 'bg-indigo-100 text-indigo-700',
        'delivered' => 'bg-teal-100 text-teal-700',
        'received' => 'bg-emerald-100 text-emerald-700',
        'canceled' => 'bg-rose-100 text-rose-600',
    ];
    $gridRows = $salesOrders->map(fn ($so) => [
        'sales_order_no' => $so->sales_order_no,
        'id' => $so->id,
        'purchase_order_no' => $so->order->order_no ?? '-',
        'store_name' => $so->store->name ?? '-',
        'item_count' => (int) $so->item_count,
        'supply_amount' => (int) $so->supply_amount,
        'status' => $so->status,
        'status_cls' => $lifecycleCls[$so->status] ?? 'bg-neutral-100 text-neutral-600',
        'status_label' => $so->status_label,
        'created_at' => $so->created_at->format('Y.m.d H:i'),
        'is_created' => $so->status === 'created',
        'confirm_url' => route($routePrefix . '.sales_orders.confirm', $so),
    ])->values();
@endphp

<x-wms.panel :title="'목록 (합계 '.number_format($salesOrders->total()).'건)'">
    <div id="salesOrdersGrid"></div>
</x-wms.panel>

<div class="mt-5">{{ $salesOrders->links() }}</div>

{{-- 판매주문 상세 팝업 --}}
@foreach ($salesOrders as $so)
    <x-detail-modal :id="$so->id">
        @include('portal.partials.sales-order-detail', ['salesOrder' => $so, 'routePrefix' => $routePrefix])
    </x-detail-modal>
@endforeach
</div>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const openModal = (id) => window.dispatchEvent(new CustomEvent('so-open', { detail: id }));
    ww.grid('salesOrdersGrid', [
        { header: '판매주문번호', name: 'sales_order_no', width: 160,
          renderer: (v, row) => {
              const b = document.createElement('button');
              b.type = 'button'; b.textContent = v;
              b.className = 'font-bold text-neutral-900 hover:text-mango-600 font-mono';
              b.addEventListener('click', () => openModal(row.id));
              return b;
          } },
        { header: '구매주문번호', name: 'purchase_order_no', width: 150,
          renderer: (v) => ww.el('span', 'font-mono text-neutral-500', v) },
        { header: '매장', name: 'store_name', width: 150 },
        { header: '품목', name: 'item_count', width: 80, align: 'right', renderer: (v) => ww.num(v) },
        { header: '공급액', name: 'supply_amount', width: 120, align: 'right',
          renderer: (v) => ww.el('span', 'font-semibold', ww.won(v)) },
        { header: '상태', name: 'status', width: 110,
          renderer: (v, row) => ww.badge(row.status_label, row.status_cls) },
        { header: '접수일', name: 'created_at', width: 150,
          renderer: (v) => ww.el('span', 'text-neutral-400', v) },
        { header: '처리', name: 'id', width: 150, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              if (row.is_created) {
                  const form = document.createElement('form');
                  form.method = 'POST'; form.action = row.confirm_url;
                  const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
                  const m = document.createElement('input'); m.type = 'hidden'; m.name = '_method'; m.value = 'PATCH'; form.appendChild(m);
                  const b = document.createElement('button');
                  b.type = 'submit'; b.textContent = '판매주문 확인';
                  b.className = 'rounded-lg bg-mango-500 hover:bg-mango-600 text-white px-3 py-1.5 font-semibold whitespace-nowrap';
                  form.appendChild(b);
                  return form;
              }
              const b = document.createElement('button');
              b.type = 'button'; b.textContent = '상세';
              b.className = 'rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1.5 font-semibold inline-block';
              b.addEventListener('click', () => openModal(row.id));
              return b;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection
