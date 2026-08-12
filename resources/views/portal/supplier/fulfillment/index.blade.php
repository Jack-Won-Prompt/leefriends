@extends('portal.layout')
@section('title', '배송 관리')

@section('content')
<div class="flex flex-wrap gap-2 mb-6">
    <a href="{{ route('portal.supplier.fulfillment.index') }}"
       class="px-4 py-2 rounded-full text-sm font-bold transition {{ $status === 'all' ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 hover:bg-mango-50' }}">전체</a>
    @foreach ($statuses as $key => $label)
        <a href="{{ route('portal.supplier.fulfillment.index', ['status' => $key]) }}"
           class="px-4 py-2 rounded-full text-sm font-bold transition {{ $status === $key ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 hover:bg-mango-50' }}">{{ $label }}</a>
    @endforeach
</div>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $items->map(fn ($it) => [
        'order_no' => $it->order->order_no ?? '-',
        'store_name' => $it->order->store->name ?? '-',
        'store_address' => $it->order->store->address ?? '',
        'product_name' => $it->product_name,
        'qty_label' => number_format($it->qty).$it->unit,
        'supply_amount' => (int) $it->supply_line_amount,
        'f_status' => $it->fulfillment_status,
        'f_label' => $it->fulfillment_label,
        'update_url' => route('portal.supplier.fulfillment.update', $it),
    ])->values();
@endphp

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">매장 직배송 품목</div>
    <div class="p-4"><div id="supplierFulfillmentGrid"></div></div>
</div>

<div class="mt-6">{{ $items->links() }}</div>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const FSTAT_CLS = {
        pending: 'bg-neutral-100 text-neutral-600',
        shipping: 'bg-sky-100 text-sky-700',
        delivered: 'bg-emerald-100 text-emerald-700',
    };
    const ACTIONS = [
        { st: 'shipping', label: '배송중', cls: 'bg-sky-100 text-sky-700 hover:bg-sky-200' },
        { st: 'delivered', label: '배송완료', cls: 'bg-emerald-500 text-white hover:bg-emerald-600' },
    ];
    ww.grid('supplierFulfillmentGrid', [
        { header: '주문번호', name: 'order_no', width: 140 },
        { header: '배송지 (매장)', name: 'store_name', width: 200,
          renderer: (v, row) => {
              const wrap = ww.el('div');
              wrap.appendChild(ww.el('div', 'font-semibold', v));
              if (row.store_address) wrap.appendChild(ww.el('div', 'text-xs text-neutral-400', row.store_address));
              return wrap;
          } },
        { header: '품목', name: 'product_name', width: 200 },
        { header: '수량', name: 'qty_label', width: 90, align: 'right' },
        { header: '공급액', name: 'supply_amount', width: 120, align: 'right', renderer: (v) => ww.won(v) },
        { header: '상태', name: 'f_status', width: 100, align: 'center',
          renderer: (v, row) => ww.badge(row.f_label, FSTAT_CLS[v] || FSTAT_CLS.pending) },
        { header: '배송 처리', name: 'update_url', width: 180, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              const wrap = ww.el('div', 'flex justify-end gap-1.5');
              ACTIONS.forEach((a) => {
                  if (row.f_status === a.st) return;
                  const form = document.createElement('form');
                  form.method = 'POST'; form.action = row.update_url;
                  const tok = document.createElement('input'); tok.type = 'hidden'; tok.name = '_token'; tok.value = CSRF; form.appendChild(tok);
                  const m = document.createElement('input'); m.type = 'hidden'; m.name = '_method'; m.value = 'PATCH'; form.appendChild(m);
                  const fs = document.createElement('input'); fs.type = 'hidden'; fs.name = 'fulfillment_status'; fs.value = a.st; form.appendChild(fs);
                  const b = document.createElement('button');
                  b.type = 'submit'; b.textContent = a.label;
                  b.className = 'rounded-lg px-3 py-1.5 font-semibold text-xs ' + a.cls;
                  form.appendChild(b);
                  wrap.appendChild(form);
              });
              return wrap;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection
