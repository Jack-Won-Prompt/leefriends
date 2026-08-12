@extends('portal.layout')
@section('title', '세금계산서')

@section('content')
<x-wms.page-head title="세금계산서" subtitle="본사가 우리 매장 앞으로 발행한 세금계산서" icon="🧾" />

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $invoices->map(function ($inv) {
        $isExempt = str_contains($inv->note ?? '', '면세');
        return [
            'invoice_no' => $inv->invoice_no,
            'is_exempt' => $isExempt,
            'invoicer_corp_name' => $inv->invoicer_corp_name,
            'supply_amount' => (int) $inv->supply_amount,
            'vat' => (int) $inv->vat,
            'total_amount' => (int) $inv->total_amount,
            'issue_date' => $inv->issue_date?->format('Y.m.d') ?? '',
            'status' => $inv->status,
            'status_label' => $inv->status_label,
            'show_url' => route('portal.store.tax_invoices.show', $inv),
        ];
    })->values();
@endphp

<x-wwgrid-tabs gid="storeTaxInvoicesGrid">
    <x-wms.panel>
        <div id="storeTaxInvoicesGrid"></div>
    </x-wms.panel>
    <div class="mt-6">{{ $invoices->links() }}</div>
</x-wwgrid-tabs>

@push('scripts')
<script>
(function () {
    const grid = ww.grid('storeTaxInvoicesGrid', [
        { header: '계산서번호', name: 'invoice_no', width: 170 },
        { header: '구분', name: 'is_exempt', width: 120, align: 'center',
          renderer: (v) => v ? ww.badge('계산서(면세)', 'bg-sky-100 text-sky-700') : ww.badge('세금계산서', 'bg-mango-100 text-mango-700') },
        { header: '공급자', name: 'invoicer_corp_name', width: 160 },
        { header: '공급가액', name: 'supply_amount', width: 120, align: 'right', renderer: (v) => ww.won(v) },
        { header: '부가세', name: 'vat', width: 110, align: 'right', renderer: (v) => ww.won(v) },
        { header: '합계', name: 'total_amount', width: 130, align: 'right', renderer: (v) => ww.won(v) },
        { header: '발행일', name: 'issue_date', width: 110 },
        { header: '상태', name: 'status', width: 100, align: 'center',
          renderer: (v, row) => ww.badge(row.status_label, v === 'canceled' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700') },
    ], @json($gridRows));

    ww.bindRowDetail('storeTaxInvoicesGrid', grid, 'show_url', 'invoice_no');
})();
</script>
@endpush
@endsection
