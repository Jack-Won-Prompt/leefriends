@extends('portal.layout')
@section('title', '세금계산서 (발행)')

@section('content')
<div x-data="{ open: null }" @open-hq-taxinvoice.window="open = $event.detail.id">
<x-wms.page-head title="세금계산서 (발행)" subtitle="본사 → 매장 발행 내역" icon="🧾" />

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $invoices->map(fn ($inv) => [
        'id' => $inv->id,
        'invoice_no' => $inv->invoice_no,
        'is_exempt' => str_contains($inv->note ?? '', '면세'),
        'invoicee_name' => $inv->invoicee_corp_name ?? optional($inv->store)->name ?? '-',
        'invoicee_email' => $inv->invoicee_email,
        'supply_amount' => (int) $inv->supply_amount,
        'vat' => (int) $inv->vat,
        'total_amount' => (int) $inv->total_amount,
        'issue_date' => $inv->issue_date?->format('Y.m.d') ?? '',
        'status' => $inv->status,
        'status_label' => $inv->status_label,
    ])->values();
@endphp

<x-wms.panel title="본사 발행 세금계산서">
    <x-slot:actions>
        <span class="text-xs font-semibold text-neutral-400">발주 상세 / 거래명세서에서 발행</span>
    </x-slot:actions>
    <div id="hqTaxInvoicesGrid"></div>
</x-wms.panel>

<div class="mt-6">{{ $invoices->links() }}</div>

{{-- 상세 팝업 --}}
@foreach ($invoices as $inv)
    <x-detail-modal :id="$inv->id">
        <x-slot:actions>
            @if ($inv->order_id)
                <a href="{{ route('portal.hq.orders.show', $inv->order_id) }}" class="rounded-xl bg-white/90 hover:bg-white text-mango-700 font-bold px-4 py-2 text-sm shadow">발주보기 →</a>
            @endif
            @if ($inv->status === 'issued')
                <form method="POST" action="{{ route('portal.hq.tax_invoices.cancel', $inv) }}"
                      onsubmit="return confirm('이 세금계산서를 발행취소합니다. 진행하시겠습니까?\n(국세청 전송 완료 후에는 취소되지 않을 수 있습니다.)')">
                    @csrf
                    <button type="submit" class="rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold px-4 py-2 text-sm shadow">발행취소</button>
                </form>
            @endif
            <button type="button" onclick="printFrame('{{ route('portal.hq.tax_invoices.print', ['invoice' => $inv, 'print' => 1]) }}')" class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm shadow">🖨️ 인쇄</button>
        </x-slot:actions>
        @include('portal.partials.tax-invoice-document', ['invoice' => $inv])
    </x-detail-modal>
@endforeach
</div>

@push('scripts')
<script>
(function () {
    const grid = ww.grid('hqTaxInvoicesGrid', [
        { header: '계산서번호', name: 'invoice_no', width: 150,
          renderer: (v) => ww.el('span', 'font-bold text-mango-700', v) },
        { header: '구분', name: 'is_exempt', width: 120, align: 'center',
          renderer: (v) => v ? ww.badge('계산서(면세)', 'bg-sky-100 text-sky-700') : ww.badge('세금계산서', 'bg-mango-100 text-mango-700') },
        { header: '공급받는자(매장)', name: 'invoicee_name', width: 200,
          renderer: (v, row) => {
              const box = document.createElement('div');
              box.appendChild(document.createTextNode(v));
              if (row.invoicee_email) {
                  const em = document.createElement('span');
                  em.className = 'block text-xs text-neutral-400'; em.textContent = row.invoicee_email;
                  box.appendChild(em);
              }
              return box;
          } },
        { header: '공급가액', name: 'supply_amount', width: 120, align: 'right', renderer: (v) => ww.won(v) },
        { header: '부가세', name: 'vat', width: 110, align: 'right',
          renderer: (v) => ww.el('span', 'text-neutral-500', ww.won(v)) },
        { header: '합계', name: 'total_amount', width: 130, align: 'right',
          renderer: (v) => ww.el('span', 'font-black text-mango-700', ww.won(v)) },
        { header: '발행일', name: 'issue_date', width: 110 },
        { header: '상태', name: 'status', width: 100, align: 'center',
          renderer: (v, row) => ww.badge(row.status_label, v === 'canceled' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700') },
    ], @json($gridRows));

    document.getElementById('hqTaxInvoicesGrid').addEventListener('click', function (e) {
        if (e.target.closest('a, button, input, select, form')) return;
        const cell = e.target.closest('[data-row-index]');
        if (!cell) return;
        const row = grid.getData()[parseInt(cell.dataset.rowIndex, 10)];
        if (!row) return;
        window.getSelection()?.removeAllRanges();
        window.dispatchEvent(new CustomEvent('open-hq-taxinvoice', { detail: { id: row.id } }));
    });
})();
</script>
@endpush
@endsection
