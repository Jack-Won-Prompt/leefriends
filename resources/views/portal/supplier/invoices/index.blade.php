@extends('portal.layout')
@section('title', '세금계산서 발행')

@section('content')
<div x-data="{ open: null }" @inv-open.window="open = $event.detail.id">
<x-wms.page-head title="세금계산서 발행" subtitle="배송완료 건을 본사에 청구·발행합니다" icon="🧾" />
<div class="grid md:grid-cols-3 gap-4 mb-6">
    <div class="md:col-span-2 rounded-2xl bg-gradient-to-br from-mango-500 to-mango-600 text-white p-6 flex items-center justify-between">
        <div>
            <p class="text-white/80 font-semibold text-sm">미청구 (배송완료) 금액</p>
            <p class="text-3xl font-black mt-1">{{ number_format($pending['amount']) }}원</p>
            <p class="text-white/80 text-sm mt-1">{{ number_format($pending['count']) }}개 품목 · 공급가 기준</p>
        </div>
        @if ($pending['count'] > 0)
            <a href="{{ route('portal.supplier.invoices.create') }}" class="rounded-xl bg-white text-mango-700 font-bold px-6 py-3.5 shadow hover:scale-105 transition shrink-0">세금계산서 발행 →</a>
        @endif
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-neutral-100">
        <p class="text-sm text-neutral-500 font-medium">발행 건수</p>
        <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($invoices->total()) }}건</p>
    </div>
</div>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $invoices->map(fn ($inv) => [
        'id' => $inv->id,
        'invoice_no' => $inv->invoice_no,
        'is_exempt' => str_contains($inv->note ?? '', '면세'),
        'supply_amount' => (int) $inv->supply_amount,
        'vat' => (int) $inv->vat,
        'total_amount' => (int) $inv->total_amount,
        'issue_date' => $inv->issue_date?->format('Y.m.d') ?? '',
        'status' => $inv->status,
        'status_label' => $inv->status_label,
        'provider_label' => $inv->provider === 'popbill' ? '팝빌' : '내부',
    ])->values();
@endphp

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">발행 내역 (본사 청구)</div>
    <div class="p-4"><div id="supplierInvoicesGrid"></div></div>
</div>

<div class="mt-6">{{ $invoices->links() }}</div>

@push('scripts')
<script>
(function () {
    ww.grid('supplierInvoicesGrid', [
        { header: '계산서번호', name: 'invoice_no', width: 160, sortable: false,
          renderer: (v, row) => {
              const b = document.createElement('button');
              b.type = 'button'; b.textContent = v;
              b.className = 'font-bold text-mango-700 hover:underline';
              b.addEventListener('click', () => window.dispatchEvent(new CustomEvent('inv-open', { detail: { id: row.id } })));
              return b;
          } },
        { header: '구분', name: 'is_exempt', width: 120, align: 'center',
          renderer: (v) => v ? ww.badge('계산서(면세)', 'bg-sky-100 text-sky-700') : ww.badge('세금계산서', 'bg-mango-100 text-mango-700') },
        { header: '공급가액', name: 'supply_amount', width: 120, align: 'right', renderer: (v) => ww.won(v) },
        { header: '부가세', name: 'vat', width: 110, align: 'right', renderer: (v) => ww.won(v) },
        { header: '합계', name: 'total_amount', width: 130, align: 'right', renderer: (v) => ww.won(v) },
        { header: '작성일', name: 'issue_date', width: 110 },
        { header: '발행', name: 'status', width: 130, align: 'center',
          renderer: (v, row) => {
              const wrap = ww.el('div', 'flex items-center justify-center gap-1');
              wrap.appendChild(ww.badge(row.status_label, v === 'canceled' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'));
              wrap.appendChild(ww.el('span', 'text-[10px] text-neutral-400', row.provider_label));
              return wrap;
          } },
    ], @json($gridRows));
})();
</script>
@endpush

{{-- 상세 팝업 --}}
@foreach ($invoices as $inv)
    <x-detail-modal :id="$inv->id">
        <x-slot:actions>
            @if ($inv->status === 'issued')
                <form method="POST" action="{{ route('portal.supplier.invoices.cancel', $inv) }}"
                      onsubmit="return confirm('이 세금계산서를 발행취소합니다. 진행하시겠습니까?\n(국세청 전송 완료 후에는 취소되지 않을 수 있습니다.)')">
                    @csrf
                    <button type="submit" class="rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold px-4 py-2 text-sm shadow">발행취소</button>
                </form>
            @endif
            <button type="button" onclick="printFrame('{{ route('portal.supplier.invoices.print', ['invoice' => $inv, 'print' => 1]) }}')" class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm shadow">🖨️ 인쇄</button>
        </x-slot:actions>
        @include('portal.partials.tax-invoice-document', ['invoice' => $inv])
    </x-detail-modal>
@endforeach
</div>
@endsection
