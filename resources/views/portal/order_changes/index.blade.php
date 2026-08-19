@extends('portal.layout')
@section('title', '매장 주문 변경')

@section('content')
@php $pending = $changes->where('acknowledged_at', null); @endphp
<x-wms.page-head title="매장 주문 변경" subtitle="매장이 수정/취소한 주문을 확인(반영)합니다" icon="⚠️">
    <x-slot:actions>
        @if ($changes->total() > 0)
            <form method="POST" action="{{ route('portal.order_changes.ack_all') }}">@csrf
                <button class="rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold px-4 py-2 text-sm">모두 반영</button>
            </form>
        @endif
    </x-slot:actions>
</x-wms.page-head>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $changes->map(fn ($c) => [
        'type_label' => $c->type_label,
        'change_type' => $c->change_type,
        'store_name' => $c->store->name ?? '매장',
        'order_no' => $c->order_no,
        'summary' => $c->summary,
        'created_at' => $c->created_at->format('Y.m.d H:i'),
        'acknowledged' => (bool) $c->acknowledged_at,
        'ack_at' => $c->acknowledged_at ? $c->acknowledged_at->format('m.d H:i') : null,
        'ack_url' => route('portal.order_changes.ack', $c),
    ])->values();
@endphp

<x-wms.panel :title="'목록 (합계 '.number_format($changes->total()).'건)'">
    <div id="orderChangesGrid"></div>
</x-wms.panel>

<div class="mt-6">{{ $changes->links() }}</div>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    ww.grid('orderChangesGrid', [
        { header: '구분', name: 'change_type', width: 100, align: 'center',
          renderer: (v, row) => ww.badge(row.type_label, v === 'canceled' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-700') },
        { header: '매장 / 주문', name: 'store_name', width: 180,
          renderer: (v, row) => {
              const wrap = document.createElement('div');
              const nm = document.createElement('span'); nm.className = 'font-bold text-neutral-900'; nm.textContent = row.store_name; wrap.appendChild(nm);
              const no = document.createElement('span'); no.className = 'block text-xs text-neutral-400'; no.textContent = row.order_no; wrap.appendChild(no);
              return wrap;
          } },
        { header: '내용', name: 'summary', width: 320 },
        { header: '발생', name: 'created_at', width: 150 },
        { header: '상태', name: 'acknowledged', width: 160, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              if (v) {
                  const s = document.createElement('span'); s.className = 'text-xs font-bold text-emerald-600';
                  s.textContent = '반영됨 · ' + row.ack_at;
                  return s;
              }
              const form = document.createElement('form');
              form.method = 'POST'; form.action = row.ack_url;
              const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
              const b = document.createElement('button');
              b.type = 'submit'; b.textContent = '확인(반영)';
              b.className = 'rounded-lg bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 font-semibold';
              form.appendChild(b);
              return form;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection
