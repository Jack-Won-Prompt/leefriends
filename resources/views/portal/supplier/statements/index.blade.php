@extends('portal.layout')
@section('title', '거래명세서 이력')

@section('content')
<x-wms.page-head title="거래명세서 이력" subtitle="거래명세서를 선택해 세금계산서를 발행합니다 (여러 건 합산 가능)" icon="📄">
    <x-slot:actions>
        <a href="{{ route('portal.supplier.statements.create') }}" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2.5 text-sm transition">+ 거래명세서 작성</a>
    </x-slot:actions>
</x-wms.page-head>

<x-date-filter :from="$from" :to="$to" label="작성일 기간" />

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $statements->map(fn ($s) => [
        'id' => $s->id,
        'statement_no' => $s->statement_no,
        'created_at' => $s->created_at->format('Y.m.d H:i'),
        'item_count' => (int) $s->item_count,
        'supply_total' => (int) $s->supply_total,
        'total' => (int) $s->total,
        'emailed' => (bool) $s->emailed_at,
        'email_count' => (int) $s->email_count,
        'emailed_at' => $s->emailed_at ? $s->emailed_at->format('Y.m.d H:i') : null,
        'tax_issued' => (bool) $s->tax_invoice_id,
        'tax_invoice_no' => $s->tax_invoice_id ? optional($s->taxInvoice)->invoice_no : null,
        'destroy_url' => route('portal.supplier.statements.destroy', $s),
    ])->values();
@endphp

<div x-data="{ open: null }" @stmt-open.window="open = $event.detail">
<x-wms.panel>
    <div id="supplierStatementsGrid"></div>
</x-wms.panel>

{{-- 선택 발행 바 --}}
<div id="ssBulkBar" class="sticky bottom-4 mt-6 rounded-2xl bg-neutral-900 text-white p-5 flex items-center justify-between shadow-lg hidden">
    <div>
        <span class="text-white/60 text-sm">거래명세서 <span class="font-bold text-white" id="ssBulkCount"></span>건 선택 · 합계</span>
        <span class="ml-2 text-2xl font-black text-mango-300"><span id="ssBulkTotal"></span>원</span>
        <span class="block text-xs text-white/40 mt-0.5">선택한 거래명세서를 합산하여 1장으로 발행합니다. (과세/면세는 자동 분리)</span>
    </div>
    <form id="ssBulkForm" method="POST" action="{{ route('portal.supplier.statements.issue_bulk') }}"
          onsubmit="return confirm('선택한 거래명세서로 세금계산서를 발행합니다. (본사 청구)\n진행하시겠습니까?')">
        @csrf
        <button type="submit" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-6 py-3 text-sm transition">🧾 세금계산서 발행</button>
    </form>
</div>

{{-- 상세 팝업 --}}
@foreach ($statements as $s)
    <x-detail-modal :id="$s->id">
        <x-slot:actions>
            <a href="{{ route('portal.supplier.statements.pdf', $s) }}" target="_blank" class="rounded-xl bg-white/90 hover:bg-white text-neutral-700 font-bold px-4 py-2 text-sm shadow">PDF</a>
            <form method="POST" action="{{ route('portal.supplier.statements.email', $s) }}"
                  onsubmit="return confirm('이 거래명세서 PDF를 본사로 이메일 전송합니다.\n진행하시겠습니까?')">
                @csrf
                <button type="submit" class="rounded-xl bg-sky-500 hover:bg-sky-600 text-white font-bold px-4 py-2 text-sm shadow">📧 {{ $s->emailed_at ? '본사 재전송' : '본사 전송' }}</button>
            </form>
            @unless ($s->tax_invoice_id)
                <form method="POST" action="{{ route('portal.supplier.statements.issue', $s) }}"
                      onsubmit="return confirm('이 거래명세서로 세금계산서를 발행합니다. (본사 청구)\n진행하시겠습니까?')">
                    @csrf
                    <button type="submit" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm shadow">🧾 세금계산서 발행</button>
                </form>
            @endunless
            <button type="button" onclick="printFrame('{{ route('portal.supplier.statements.print', ['statement' => $s, 'print' => 1]) }}')" class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm shadow">🖨️ 인쇄</button>
        </x-slot:actions>
        @include('portal.partials.supplier-statement-document', ['statement' => $s])
    </x-detail-modal>
@endforeach
</div>

<div class="mt-6">{{ $statements->links() }}</div>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const openModal = (id) => window.dispatchEvent(new CustomEvent('stmt-open', { detail: id }));

    const grid = ww.grid('supplierStatementsGrid', [
        { header: '명세서번호', name: 'statement_no', width: 180,
          renderer: (v, row) => {
              const b = document.createElement('button');
              b.type = 'button'; b.textContent = v;
              b.className = 'font-bold text-mango-700 hover:underline';
              b.addEventListener('click', () => openModal(row.id));
              return b;
          } },
        { header: '작성일', name: 'created_at', width: 150,
          renderer: (v) => ww.el('span', 'text-neutral-500', v) },
        { header: '품목', name: 'item_count', width: 90, align: 'right',
          renderer: (v) => ww.el('span', 'text-neutral-500', ww.num(v) + '건') },
        { header: '공급가액', name: 'supply_total', width: 130, align: 'right',
          renderer: (v) => ww.num(v) + '원' },
        { header: '합계', name: 'total', width: 130, align: 'right',
          renderer: (v) => ww.el('span', 'font-black text-mango-700', ww.won(v)) },
        { header: '거래명세서 본사 전송', name: 'emailed', width: 170,
          renderer: (v, row) => {
              if (v) {
                  const box = document.createElement('div');
                  box.appendChild(ww.el('span', 'text-xs font-bold px-2.5 py-1 rounded-full bg-sky-100 text-sky-700', '전송됨' + (row.email_count > 1 ? ' ' + row.email_count : '')));
                  box.appendChild(ww.el('span', 'block text-[11px] text-neutral-400 mt-0.5', row.emailed_at || ''));
                  return box;
              }
              return ww.el('span', 'text-xs font-bold px-2.5 py-1 rounded-full bg-neutral-100 text-neutral-400', '미전송');
          } },
        { header: '세금계산서', name: 'tax_issued', width: 130,
          renderer: (v, row) => {
              if (v) {
                  const box = document.createElement('div');
                  box.appendChild(ww.el('span', 'text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700', '발행완료'));
                  box.appendChild(ww.el('span', 'block text-[11px] text-neutral-400 mt-0.5', row.tax_invoice_no || ''));
                  return box;
              }
              return ww.el('span', 'text-xs font-bold px-2.5 py-1 rounded-full bg-neutral-100 text-neutral-400', '미발행');
          } },
        { header: '관리', name: 'id', width: 120, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              const wrap = document.createElement('div');
              wrap.className = 'flex items-center justify-end whitespace-nowrap';
              const detail = document.createElement('button');
              detail.type = 'button'; detail.textContent = '상세';
              detail.className = 'text-xs font-bold text-neutral-500 hover:text-mango-600 mr-3';
              detail.addEventListener('click', () => openModal(row.id));
              wrap.appendChild(detail);
              if (!row.tax_issued) {
                  const form = document.createElement('form');
                  form.method = 'POST'; form.action = row.destroy_url; form.className = 'inline';
                  form.addEventListener('submit', (e) => { if (!confirm('이 거래명세서를 삭제할까요?')) e.preventDefault(); });
                  const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
                  const m = document.createElement('input'); m.type = 'hidden'; m.name = '_method'; m.value = 'DELETE'; form.appendChild(m);
                  const db = document.createElement('button');
                  db.type = 'submit'; db.textContent = '삭제';
                  db.className = 'text-xs font-bold text-rose-500 hover:text-rose-600';
                  form.appendChild(db); wrap.appendChild(form);
              }
              return wrap;
          } },
    ], @json($gridRows));

    // 일괄 발행 바 — 그리드 선택(미발행 건만) 구동
    const bar = document.getElementById('ssBulkBar');
    const bulkForm = document.getElementById('ssBulkForm');
    const cntEl = document.getElementById('ssBulkCount');
    const totalEl = document.getElementById('ssBulkTotal');
    const issuable = () => grid.getCheckedRows().filter((r) => !r.tax_issued);

    function refreshBar() {
        const rows = issuable();
        if (rows.length === 0) { bar.classList.add('hidden'); return; }
        bar.classList.remove('hidden');
        cntEl.textContent = rows.length;
        totalEl.textContent = rows.reduce((s, r) => s + (Number(r.total) || 0), 0).toLocaleString();
    }
    document.getElementById('supplierStatementsGrid').addEventListener('change', refreshBar);

    bulkForm.addEventListener('submit', function () {
        bulkForm.querySelectorAll('input[name="statement_ids[]"]').forEach((n) => n.remove());
        issuable().forEach((r) => {
            const i = document.createElement('input');
            i.type = 'hidden'; i.name = 'statement_ids[]'; i.value = r.id;
            bulkForm.appendChild(i);
        });
    });
})();
</script>
@endpush
@endsection
