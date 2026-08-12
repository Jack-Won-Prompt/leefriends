@extends('portal.layout')
@section('title', '세금계산서 (수취)')

@section('content')
<div x-data="{ open: null }" @open-hq-invoice.window="open = $event.detail.id">
<x-wms.page-head title="세금계산서 (수취)" subtitle="공급처가 발행한 세금계산서" icon="🧮" />
<x-date-filter :from="$from" :to="$to" label="발행일 기간" />
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="rounded-2xl bg-white p-5 shadow-sm border border-neutral-100">
        <p class="text-sm text-neutral-500 font-medium">발행 건수</p>
        <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($totals['count']) }}건</p>
    </div>
    <div class="rounded-2xl bg-neutral-900 text-white p-5">
        <p class="text-sm text-white/70 font-medium">수취 합계금액</p>
        <p class="text-3xl font-black text-mango-300 mt-1">{{ number_format($totals['amount']) }}원</p>
    </div>
</div>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $invoices->map(fn ($inv) => [
        'id' => $inv->id,
        'invoice_no' => $inv->invoice_no,
        'supplier_name' => $inv->invoicer_corp_name ?? optional($inv->supplier)->name ?? '-',
        'supply_amount' => (int) $inv->supply_amount,
        'vat' => (int) $inv->vat,
        'total_amount' => (int) $inv->total_amount,
        'issue_date' => $inv->issue_date?->format('Y.m.d') ?? '',
        'status' => $inv->status,
        'status_label' => $inv->status_label,
    ])->values();
@endphp

<x-wms.panel title="공급처 발행 세금계산서">
    <div id="hqInvoicesGrid"></div>
</x-wms.panel>

<div class="mt-6">{{ $invoices->links() }}</div>

{{-- 상세 팝업 --}}
@foreach ($invoices as $inv)
    <x-detail-modal :id="$inv->id">
        <x-slot:actions>
            <button type="button" onclick="printFrame('{{ route('portal.hq.invoices.print', ['invoice' => $inv, 'print' => 1]) }}')"
                    class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm shadow">🖨️ 인쇄</button>
        </x-slot:actions>
        @include('portal.partials.tax-invoice-document', ['invoice' => $inv])
    </x-detail-modal>
@endforeach
</div>

@push('scripts')
<script>
(function () {
    const grid = ww.grid('hqInvoicesGrid', [
        { header: '계산서번호', name: 'invoice_no', width: 150,
          renderer: (v) => ww.el('span', 'font-bold text-mango-700', v) },
        { header: '공급처', name: 'supplier_name', width: 180 },
        { header: '공급가액', name: 'supply_amount', width: 120, align: 'right', renderer: (v) => ww.won(v) },
        { header: '부가세', name: 'vat', width: 110, align: 'right',
          renderer: (v) => ww.el('span', 'text-neutral-500', ww.won(v)) },
        { header: '합계', name: 'total_amount', width: 130, align: 'right',
          renderer: (v) => ww.el('span', 'font-black text-mango-700', ww.won(v)) },
        { header: '작성일', name: 'issue_date', width: 110 },
        { header: '상태', name: 'status', width: 100, align: 'center',
          renderer: (v, row) => ww.badge(row.status_label, v === 'canceled' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700') },
    ], @json($gridRows));

    document.getElementById('hqInvoicesGrid').addEventListener('click', function (e) {
        if (e.target.closest('a, button, input, select, form')) return;
        const cell = e.target.closest('[data-row-index]');
        if (!cell) return;
        const row = grid.getData()[parseInt(cell.dataset.rowIndex, 10)];
        if (!row) return;
        window.getSelection()?.removeAllRanges();
        window.dispatchEvent(new CustomEvent('open-hq-invoice', { detail: { id: row.id } }));
    });
})();
</script>
@endpush
@endsection
