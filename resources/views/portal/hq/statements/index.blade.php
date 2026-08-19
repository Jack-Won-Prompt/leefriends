@extends('portal.layout')
@section('title', '거래명세서')

@section('content')
<div x-data="{ open: null }" @stmt-open.window="open = $event.detail">
<x-wms.page-head title="거래명세서" subtitle="작성·발송한 거래명세서 이력입니다. PDF 재보기·재전송할 수 있습니다." icon="🧾" />

<x-date-filter :from="$from" :to="$to" label="발송일 기간">
    <x-slot:actions>
        <a href="{{ route('portal.hq.statements.create') }}" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 거래명세서 작성</a>
        <a href="{{ route('portal.hq.statements.excel_bulk', ['from' => $from, 'to' => $to]) }}"
           id="btnStmtExcelBulk"
           class="inline-flex items-center gap-1 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 text-sm font-bold transition">⬇️ 엑셀 (선택 / 전체)</a>
    </x-slot:actions>
</x-date-filter>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $statements->map(function ($s) {
        $issuable = ! $s->tax_invoice_id && optional($s->store)->biz_no;
        return [
            'transmit_label' => '전송됨' . ($s->resend_count > 0 ? ' (재전송 ' . $s->resend_count . ')' : ''),
            'sent_at' => $s->sent_at->format('Y.m.d H:i'),
            'store_name' => $s->store_name,
            'order_no' => $s->order ? $s->order->order_no : null,
            'email' => $s->email ?: '-',
            'item_count' => (int) $s->item_count,
            'total' => (int) $s->total,
            'sender_name' => optional($s->sender)->name ?? '본사',
            'receipt_status' => $s->receiptStatus(),
            'receipt_label' => $s->receiptLabel(),
            'confirmed_at' => $s->confirmed_at ? $s->confirmed_at->format('m.d H:i') : null,
            'tax_state' => $s->tax_invoice_id ? 'issued' : ($issuable ? 'issuable' : 'no_biz'),
            'tax_invoice_no' => $s->tax_invoice_id ? optional($s->taxInvoice)->invoice_no : null,
            'tax_issue_url' => route('portal.hq.tax_invoices.issue_statement', $s),
            'tax_issue_confirm' => "이 거래명세서로 세금계산서를 발행합니다.\n수신: {$s->store_name} ({$s->email})\n진행하시겠습니까?",
            'id' => $s->id,
            'pdf_url' => route('portal.hq.statements.pdf', $s),
            'excel_url' => route('portal.hq.statements.excel', $s),
            'resend_url' => route('portal.hq.statements.resend', $s),
            'resend_confirm' => "«{$s->store_name}»({$s->email})로 거래명세서를 재전송할까요?",
            'has_email' => (bool) $s->email,
        ];
    })->values();
@endphp

<x-wms.panel :title="'발송 이력 (합계 '.number_format($statements->total()).'건)'">
    <div id="hqStatementsGrid"></div>
</x-wms.panel>

@if ($statements->hasPages())
    <div class="mt-5">{{ $statements->links() }}</div>
@endif

{{-- 상세 팝업 --}}
@foreach ($statements as $s)
    <x-detail-modal :id="$s->id">
        <x-slot:actions>
            <a href="{{ route('portal.hq.statements.pdf', $s) }}" target="_blank" class="rounded-xl bg-white/90 hover:bg-white text-neutral-700 font-bold px-4 py-2 text-sm shadow">PDF</a>
            <a href="{{ route('portal.hq.statements.excel', $s) }}" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 text-sm shadow">⬇️ 엑셀</a>
            @if (! $s->tax_invoice_id && optional($s->store)->biz_no)
                <form method="POST" action="{{ route('portal.hq.tax_invoices.issue_statement', $s) }}"
                      onsubmit="return confirm('이 거래명세서로 세금계산서를 발행합니다.\n수신: {{ $s->store_name }} ({{ $s->email }})\n진행하시겠습니까?')">
                    @csrf
                    <button type="submit" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm shadow">🧾 세금계산서 발행</button>
                </form>
            @endif
            <button type="button" onclick="printFrame('{{ route('portal.hq.statements.print', ['statement' => $s, 'print' => 1]) }}')" class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm shadow">🖨️ 인쇄</button>
        </x-slot:actions>
        @include('portal.partials.hq-statement-document', ['statement' => $s, 'editable' => true])
    </x-detail-modal>
@endforeach
</div>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const RECEIPT_CLS = {
        pending: 'bg-neutral-100 text-neutral-400',
        viewed: 'bg-amber-100 text-amber-700',
        confirmed: 'bg-emerald-100 text-emerald-700',
    };
    const openModal = (id) => window.dispatchEvent(new CustomEvent('stmt-open', { detail: id }));

    const grid = ww.grid('hqStatementsGrid', [
        { header: '거래명세서 매장 전송', name: 'transmit_label', width: 170,
          renderer: (v, row) => {
              const box = document.createElement('div');
              box.appendChild(ww.badge(v, 'bg-sky-100 text-sky-700'));
              const sub = document.createElement('span');
              sub.className = 'block text-[11px] text-neutral-400 mt-0.5';
              sub.textContent = row.sent_at;
              box.appendChild(sub);
              return box;
          } },
        { header: '매장', name: 'store_name', width: 150,
          renderer: (v, row) => {
              const box = document.createElement('div');
              const b = document.createElement('button');
              b.type = 'button'; b.textContent = v;
              b.className = 'font-bold text-mango-700 hover:underline';
              b.addEventListener('click', () => openModal(row.id));
              box.appendChild(b);
              if (row.order_no) {
                  const sub = document.createElement('span');
                  sub.className = 'block text-[11px] text-sky-600 font-bold mt-0.5';
                  sub.textContent = '발주 ' + row.order_no;
                  box.appendChild(sub);
              }
              return box;
          } },
        { header: '수신 이메일', name: 'email', width: 200,
          renderer: (v) => ww.el('span', 'text-neutral-500', v) },
        { header: '품목', name: 'item_count', width: 90, align: 'right', renderer: (v) => ww.num(v) + '건' },
        { header: '합계', name: 'total', width: 120, align: 'right',
          renderer: (v) => ww.el('span', 'font-black text-mango-700', ww.won(v)) },
        { header: '발송자', name: 'sender_name', width: 110,
          renderer: (v) => ww.el('span', 'text-neutral-500', v) },
        { header: '매장 확인', name: 'receipt_label', width: 120,
          renderer: (v, row) => {
              const box = document.createElement('div');
              box.appendChild(ww.badge(v, RECEIPT_CLS[row.receipt_status] || RECEIPT_CLS.pending));
              if (row.confirmed_at) {
                  const sub = document.createElement('span');
                  sub.className = 'block text-[11px] text-neutral-400 mt-0.5';
                  sub.textContent = row.confirmed_at;
                  box.appendChild(sub);
              }
              return box;
          } },
        { header: '세금계산서', name: 'tax_state', width: 140,
          renderer: (v, row) => {
              if (v === 'issued') {
                  const box = document.createElement('div');
                  box.appendChild(ww.badge('발행완료', 'bg-emerald-100 text-emerald-700'));
                  const sub = document.createElement('span');
                  sub.className = 'block text-[11px] text-neutral-400 mt-0.5';
                  sub.textContent = row.tax_invoice_no || '';
                  box.appendChild(sub);
                  return box;
              }
              if (v === 'no_biz') {
                  return ww.el('span', 'text-[11px] text-amber-600', '사업자정보 없음');
              }
              const form = document.createElement('form');
              form.method = 'POST'; form.action = row.tax_issue_url;
              form.addEventListener('submit', (e) => { if (!confirm(row.tax_issue_confirm)) e.preventDefault(); });
              const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
              const b = document.createElement('button');
              b.type = 'submit'; b.textContent = '🧾 발행';
              b.className = 'text-xs font-bold text-mango-600 hover:text-mango-700';
              form.appendChild(b);
              return form;
          } },
        { header: '관리', name: 'id', width: 180, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              const wrap = document.createElement('div');
              wrap.className = 'flex items-center justify-end gap-3';
              const detail = document.createElement('button');
              detail.type = 'button'; detail.textContent = '상세';
              detail.className = 'text-xs font-bold text-neutral-500 hover:text-mango-600';
              detail.addEventListener('click', () => openModal(row.id));
              wrap.appendChild(detail);
              const pdf = document.createElement('a');
              pdf.href = row.pdf_url; pdf.target = '_blank'; pdf.textContent = 'PDF';
              pdf.className = 'text-xs font-bold text-neutral-700 hover:text-mango-600';
              wrap.appendChild(pdf);
              const excel = document.createElement('a');
              excel.href = row.excel_url; excel.textContent = '엑셀';
              excel.className = 'text-xs font-bold text-emerald-600 hover:text-emerald-700';
              wrap.appendChild(excel);
              const form = document.createElement('form');
              form.method = 'POST'; form.action = row.resend_url; form.className = 'inline';
              form.addEventListener('submit', (e) => { if (!confirm(row.resend_confirm)) e.preventDefault(); });
              const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
              const rb = document.createElement('button');
              rb.type = 'submit'; rb.textContent = '재전송';
              rb.className = 'text-xs font-bold text-mango-600 hover:text-mango-700';
              if (!row.has_email) { rb.disabled = true; rb.classList.add('opacity-30', 'cursor-not-allowed'); }
              form.appendChild(rb);
              wrap.appendChild(form);
              return wrap;
          } },
    ], @json($gridRows));

    // 엑셀: 체크된 거래명세서가 있으면 해당 건만, 없으면 조회된 전체(기간필터) 다운로드
    const stmtExcelBtn = document.getElementById('btnStmtExcelBulk');
    if (stmtExcelBtn) stmtExcelBtn.addEventListener('click', function (e) {
        e.preventDefault();
        let url = stmtExcelBtn.getAttribute('href');
        const rows = grid.getCheckedRows();
        if (rows.length) {
            const qs = rows.map((r) => 'ids[]=' + encodeURIComponent(r.id)).join('&');
            url += (url.indexOf('?') === -1 ? '?' : '&') + qs;
        }
        window.location = url;
    });
})();
</script>
@endpush
@endsection
