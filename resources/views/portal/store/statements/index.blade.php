@extends('portal.layout')
@section('title', '거래명세서(수취)')

@section('content')
<x-wms.page-head title="거래명세서(수취)" subtitle="본사가 발송한 거래명세서를 확인하고 PDF로 볼 수 있습니다." icon="🧾" />

<x-date-filter :from="$from" :to="$to" label="발송일 기간" />

@include('portal.partials.wwgrid-assets')
@php
    $rcMap = ['pending'=>'bg-neutral-100 text-neutral-500','viewed'=>'bg-amber-100 text-amber-700','confirmed'=>'bg-emerald-100 text-emerald-700'];
    $gridRows = $statements->map(fn ($s) => [
        'issue_date' => $s->issueDate()->format('Y.m.d'),
        'item_count' => (int) $s->item_count,
        'total' => (int) $s->total,
        'sent_at' => optional($s->sent_at)->format('Y.m.d H:i') ?? '',
        'receipt_cls' => $rcMap[$s->receiptStatus()],
        'receipt_label' => $s->receiptLabel(),
        'confirmed_at' => $s->confirmed_at?->format('m.d H:i'),
        'confirmed' => (bool) $s->confirmed_at,
        'pdf_url' => route('portal.store.statements.pdf', $s),
        'confirm_url' => route('portal.store.statements.confirm', $s),
    ])->values();
@endphp

<x-wms.panel>
    <div id="storeStatementsGrid"></div>
</x-wms.panel>
@if ($statements->hasPages())
    <div class="mt-5">{{ $statements->links() }}</div>
@endif

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    ww.grid('storeStatementsGrid', [
        { header: '발행일자', name: 'issue_date', width: 120 },
        { header: '품목수', name: 'item_count', width: 90, align: 'right', renderer: (v) => ww.num(v) },
        { header: '금액', name: 'total', width: 130, align: 'right', renderer: (v) => ww.won(v) },
        { header: '수신일시', name: 'sent_at', width: 150 },
        { header: '상태', name: 'receipt_label', width: 130, sortable: false,
          renderer: (v, row) => {
              const wrap = ww.el('div');
              wrap.appendChild(ww.badge(row.receipt_label, row.receipt_cls));
              if (row.confirmed_at) wrap.appendChild(ww.el('span', 'block text-[11px] text-neutral-400 mt-0.5', row.confirmed_at));
              return wrap;
          } },
        { header: '명세서', name: 'actions', width: 190, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              const wrap = ww.el('div', 'flex items-center justify-end gap-1');
              const a = ww.el('a', 'inline-flex items-center gap-1 rounded-lg bg-mango-500 hover:bg-mango-600 text-white font-bold px-3 py-1.5 text-xs transition', '🧾 PDF 보기');
              a.href = row.pdf_url; a.target = '_blank';
              wrap.appendChild(a);
              if (!row.confirmed) {
                  const form = document.createElement('form');
                  form.method = 'POST'; form.action = row.confirm_url; form.className = 'inline';
                  form.addEventListener('submit', (e) => { if (!confirm('이 거래명세서를 확인 처리할까요? 본사에 통보됩니다.')) e.preventDefault(); });
                  const t = ww.el('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
                  const b = ww.el('button', 'inline-flex items-center gap-1 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-3 py-1.5 text-xs transition', '✔ 확인');
                  b.type = 'submit'; form.appendChild(b);
                  wrap.appendChild(form);
              }
              return wrap;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection
