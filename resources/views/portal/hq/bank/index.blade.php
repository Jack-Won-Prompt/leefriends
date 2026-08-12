@extends('portal.layout')
@section('title', '계좌연동 입금확인')

@section('content')
@php $polling = $selected && ! $selected->isDone(); @endphp

<x-wms.page-head title="계좌연동 입금확인" subtitle="등록된 계좌의 입금내역을 수집하고, 입금자↔매장 매핑으로 매장 주문과 대사합니다." icon="🏦">
    <x-slot:actions>
        <form method="POST" action="{{ route('portal.hq.bank.auto_charge') }}" class="inline">
            @csrf
            <input type="hidden" name="acc" value="{{ $selAcc }}">
            <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-4 py-2 text-sm transition">💰 예치금 자동충전</button>
        </form>
        <form method="POST" action="{{ route('portal.hq.bank.auto_match') }}" class="inline">
            @csrf
            <input type="hidden" name="acc" value="{{ $selAcc }}">
            <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">⚡ 자동 대사</button>
        </form>
    </x-slot:actions>
</x-wms.page-head>

@if ($accountsError)
    <div class="mb-5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-5 py-3.5 text-sm font-medium">
        계좌 목록 조회 실패: {{ $accountsError }} <span class="text-amber-500">— 운영(IsTest=false)에서 팝빌 콘솔에 계좌를 등록한 뒤 이용하세요.</span>
    </div>
@elseif (empty($accounts))
    <div class="mb-5 rounded-xl bg-neutral-50 border border-neutral-200 text-neutral-500 px-5 py-3.5 text-sm">
        등록된 계좌가 없습니다. 팝빌 홈페이지 → 계좌조회에서 계좌를 등록해 주세요.
    </div>
@endif

<div x-data="bank({{ $polling ? 'true' : 'false' }}, '{{ $selected->job_id ?? '' }}')"
     @bank-openmap.window="openMap($event.detail.depositor, $event.detail.storeId)">

{{-- 계좌 선택 + 기간 수집 --}}
<x-wms.panel class="mb-5">
    <form method="GET" action="{{ route('portal.hq.bank.index') }}" class="px-5 pt-5 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">계좌</label>
            <select name="acc" onchange="this.form.submit()" class="rounded-xl border-neutral-200 text-sm py-2 min-w-[15rem]">
                @foreach ($accounts as $a)
                    <option value="{{ $a->bankCode }}|{{ $a->accountNumber }}" @selected($selAcc === $a->bankCode.'|'.$a->accountNumber)>
                        {{ $a->accountName ?: '계좌' }} · {{ $a->accountNumber }}
                    </option>
                @endforeach
            </select>
        </div>
    </form>
    <form method="POST" action="{{ route('portal.hq.bank.request') }}" class="px-5 pb-5 pt-3 flex flex-wrap items-end gap-3">
        @csrf
        <input type="hidden" name="acc" value="{{ $selAcc }}">
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">시작일</label>
            <input type="date" name="start_date" value="{{ old('start_date', $defStart) }}" class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">종료일</label>
            <input type="date" name="end_date" value="{{ old('end_date', $defEnd) }}" class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <button type="submit" @disabled(! $selAcc) class="inline-flex items-center gap-1 rounded-xl bg-neutral-800 hover:bg-neutral-900 disabled:opacity-40 text-white font-bold px-5 py-2.5 text-sm transition">🔄 입금내역 수집</button>
    </form>
</x-wms.panel>

{{-- 수집 진행 --}}
<div x-show="polling" x-cloak class="mb-5 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 px-5 py-3.5 text-sm font-medium flex items-center gap-2">
    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
    <span x-text="stateLabel">수집 중…</span> — 완료되면 자동으로 입금내역이 표시됩니다.
</div>

{{-- 요약 --}}
@if ($deposits->isNotEmpty())
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
    <x-wms.stat label="입금 건수" :value="number_format($summary['count']).'건'" variant="info" icon="🧾" />
    <x-wms.stat label="입금 합계" :value="number_format($summary['total']).'원'" variant="success" icon="💰" />
    <x-wms.stat label="대사 완료" :value="number_format($summary['matched']).'건'" variant="accent" />
    <x-wms.stat label="미대사" :value="number_format($summary['unmatched']).'건'" variant="warn" />
</div>
@endif

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $deposits->map(function ($d) use ($resolvedStore, $storeById, $candidates, $chargedDepositIds) {
        $sid = $resolvedStore[$d->id] ?? null;
        $store = $sid ? ($storeById[$sid] ?? null) : null;
        $cands = $candidates[$d->id] ?? collect();

        return [
            'id' => $d->id,
            'trade_date' => (string) \Illuminate\Support\Str::of((string) $d->trade_date)->replaceMatches('/(\d{4})(\d{2})(\d{2})/', '$1.$2.$3'),
            'depositor' => $d->depositor,
            'depositor_label' => $d->depositor ?: '(입금자명 없음)',
            'remark' => $d->remark,
            'acc_in' => (int) $d->acc_in,
            'has_store' => (bool) $store,
            'store_name' => $store?->name,
            'store_id' => $sid,
            'is_matched' => $d->isMatched(),
            'matched_order_no' => optional($d->matchedOrder)->order_no,
            'unmatch_url' => route('portal.hq.bank.unmatch', $d),
            'cands' => $cands->map(fn ($o) => ['id' => $o->id, 'order_no' => $o->order_no, 'order_total' => (int) $o->order_total])->values(),
            'charged' => isset($chargedDepositIds[$d->id]),
            'charge_suffix' => $store ? ($store->settlement_type === 'prepaid' ? '' : ' (후불매장)') : '',
            'charge_confirm' => $store ? ($store->name.' 예치금으로 '.number_format((int) $d->acc_in).'원을 충전할까요?') : '',
        ];
    })->values();
@endphp

{{-- 여러 입금자 → 한 매장 일괄 매핑 툴바 --}}
<div id="bankBulkBar" class="mb-3 flex flex-wrap items-center gap-3 rounded-xl bg-mango-50 border border-mango-200 px-4 py-3 hidden">
    <span class="text-sm font-bold text-mango-800">선택한 입금자 <span id="bankBulkCount"></span>명</span>
    <form id="bankBulkForm" method="POST" action="{{ route('portal.hq.bank.map_bulk') }}" class="flex flex-wrap items-center gap-2">
        @csrf
        <input type="hidden" name="acc" value="{{ $selAcc }}">
        <select name="store_id" id="bankBulkStore" class="rounded-xl border-neutral-200 text-sm py-2">
            <option value="">매장 선택…</option>
            @foreach ($stores as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
        </select>
        <button type="submit" id="bankBulkSubmit" disabled class="rounded-xl bg-mango-500 hover:bg-mango-600 disabled:opacity-40 text-white font-bold px-4 py-2 text-sm transition">선택 매핑</button>
        <button type="button" id="bankBulkClear" class="text-xs text-neutral-500 hover:underline">선택 해제</button>
    </form>
</div>

{{-- 입금내역 --}}
<x-wms.panel>
    <div id="hqBankGrid"></div>
</x-wms.panel>

{{-- 입금자 → 매장 매핑 모달 --}}
<div x-show="mapOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" @click="mapOpen = false"></div>
    <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
            <h3 class="font-bold text-neutral-800">입금자 → 매장 매핑</h3>
            <button type="button" @click="mapOpen = false" class="text-neutral-400 hover:text-neutral-600 text-xl leading-none">✕</button>
        </div>
        <form method="POST" action="{{ route('portal.hq.bank.map') }}" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="acc" value="{{ $selAcc }}">
            <div>
                <label class="block text-xs font-semibold text-neutral-500 mb-1">입금자명</label>
                <input type="text" name="depositor_name" x-model="mapDepositorName" readonly class="w-full rounded-xl border-neutral-200 bg-neutral-50 text-sm py-2">
            </div>
            <div>
                <label class="block text-xs font-semibold text-neutral-500 mb-1">매장</label>
                <select name="store_id" x-model="mapStoreId" class="w-full rounded-xl border-neutral-200 text-sm py-2">
                    <option value="">매장 선택…</option>
                    @foreach ($stores as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-xs text-neutral-400">저장하면 이후 같은 입금자명은 자동으로 이 매장으로 인식됩니다.</p>
            <div class="flex justify-end gap-2 pt-1">
                <button type="button" @click="mapOpen = false" class="rounded-xl border border-neutral-200 px-4 py-2 text-sm font-bold hover:bg-neutral-50">취소</button>
                <button type="submit" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white px-4 py-2 text-sm font-bold">저장</button>
            </div>
        </form>
    </div>
</div>

</div>

<script>
function bank(polling, jobId) {
    return {
        polling: polling, jobId: jobId, stateLabel: '수집 중…',
        mapOpen: false, mapDepositorName: '', mapStoreId: '',
        init() { if (this.polling && this.jobId) this.poll(); },
        poll() {
            fetch(`{{ url('portal/hq/bank/jobs') }}/${this.jobId}/state`, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(d => {
                    if (!d.ok) { this.stateLabel = '상태 확인 실패'; return; }
                    this.stateLabel = d.label;
                    if (d.done) { window.location.reload(); return; }
                    setTimeout(() => this.poll(), 3000);
                })
                .catch(() => setTimeout(() => this.poll(), 4000));
        },
        openMap(depositor, storeId) {
            this.mapDepositorName = depositor || '';
            this.mapStoreId = storeId ? String(storeId) : '';
            this.mapOpen = true;
        },
    };
}
</script>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const MATCH_URL = '{{ route('portal.hq.bank.match') }}';
    const CHARGE_URL = '{{ route('portal.hq.bank.charge') }}';
    const openMap = (depositor, storeId) =>
        window.dispatchEvent(new CustomEvent('bank-openmap', { detail: { depositor: depositor || '', storeId: storeId || null } }));

    const hidden = (name, value) => {
        const i = document.createElement('input');
        i.type = 'hidden'; i.name = name; i.value = value;
        return i;
    };

    const grid = ww.grid('hqBankGrid', [
        { header: '거래일', name: 'trade_date', width: 110,
          renderer: (v) => ww.el('span', 'text-neutral-600', v) },
        { header: '입금자', name: 'depositor_label', width: 200,
          renderer: (v, row) => {
              const box = document.createElement('div');
              box.appendChild(ww.el('span', 'font-medium text-neutral-800', v));
              if (row.remark) box.appendChild(ww.el('span', 'block text-xs text-neutral-400', row.remark));
              return box;
          } },
        { header: '매장(매핑)', name: 'has_store', width: 180,
          renderer: (v, row) => {
              const box = document.createElement('div');
              if (v) {
                  box.appendChild(ww.el('span', 'inline-flex items-center gap-1 text-emerald-700 font-semibold', '🏪 ' + (row.store_name || '')));
                  const b = document.createElement('button');
                  b.type = 'button'; b.textContent = '매핑 변경';
                  b.className = 'block text-xs text-neutral-400 hover:underline mt-0.5';
                  b.addEventListener('click', () => openMap(row.depositor, row.store_id));
                  box.appendChild(b);
              } else {
                  const b = document.createElement('button');
                  b.type = 'button'; b.textContent = '＋ 매장 매핑';
                  b.className = 'inline-flex items-center gap-1 rounded-lg bg-amber-100 text-amber-700 font-bold px-2.5 py-1 text-xs hover:bg-amber-200';
                  b.addEventListener('click', () => openMap(row.depositor, null));
                  box.appendChild(b);
              }
              return box;
          } },
        { header: '입금액', name: 'acc_in', width: 110, align: 'right',
          renderer: (v) => ww.el('span', 'tabular-nums font-bold text-emerald-600', ww.num(v)) },
        { header: '대사', name: 'id', width: 290, sortable: false, exportable: false,
          renderer: (v, row) => {
              const wrap = document.createElement('div');
              if (row.is_matched) {
                  const line = document.createElement('div');
                  line.className = 'flex items-center gap-2';
                  line.appendChild(ww.el('span', 'inline-flex items-center gap-1 rounded-lg bg-mango-100 text-mango-700 font-bold px-2.5 py-1 text-xs', '✔ ' + (row.matched_order_no || '')));
                  const f = document.createElement('form');
                  f.method = 'POST'; f.action = row.unmatch_url;
                  f.addEventListener('submit', (e) => { if (!confirm('대사를 해제할까요?')) e.preventDefault(); });
                  f.appendChild(hidden('_token', CSRF));
                  f.appendChild(hidden('_method', 'DELETE'));
                  const ub = document.createElement('button');
                  ub.type = 'submit'; ub.textContent = '해제';
                  ub.className = 'text-xs text-neutral-400 hover:text-rose-500';
                  f.appendChild(ub); line.appendChild(f);
                  wrap.appendChild(line);
              } else if (!row.has_store) {
                  wrap.appendChild(ww.el('span', 'text-xs text-neutral-400', '입금자 매핑 후 대사 가능'));
              } else if (!row.cands || row.cands.length === 0) {
                  wrap.appendChild(ww.el('span', 'text-xs text-neutral-400', '금액이 일치하는 미입금 주문 없음'));
              } else {
                  const list = document.createElement('div');
                  list.className = 'flex flex-wrap gap-1.5';
                  row.cands.forEach((o) => {
                      const f = document.createElement('form');
                      f.method = 'POST'; f.action = MATCH_URL;
                      f.appendChild(hidden('_token', CSRF));
                      f.appendChild(hidden('deposit_id', row.id));
                      f.appendChild(hidden('order_id', o.id));
                      const b = document.createElement('button');
                      b.type = 'submit';
                      b.className = 'inline-flex items-center gap-1 rounded-lg border border-mango-300 text-mango-700 hover:bg-mango-50 font-bold px-2.5 py-1 text-xs';
                      b.textContent = o.order_no + ' · ' + ww.num(o.order_total) + '원 대사';
                      f.appendChild(b); list.appendChild(f);
                  });
                  wrap.appendChild(list);
              }

              // 예치금 충전 (주문 대사와 병행)
              if (row.has_store) {
                  const cw = document.createElement('div');
                  cw.className = 'mt-1.5';
                  if (row.charged) {
                      cw.appendChild(ww.el('span', 'inline-flex items-center gap-1 rounded-lg bg-emerald-100 text-emerald-700 font-bold px-2.5 py-1 text-xs', '💰 예치금 충전됨'));
                  } else {
                      const f = document.createElement('form');
                      f.method = 'POST'; f.action = CHARGE_URL;
                      f.addEventListener('submit', (e) => { if (!confirm(row.charge_confirm)) e.preventDefault(); });
                      f.appendChild(hidden('_token', CSRF));
                      f.appendChild(hidden('deposit_id', row.id));
                      f.appendChild(hidden('store_id', row.store_id));
                      const b = document.createElement('button');
                      b.type = 'submit';
                      b.className = 'inline-flex items-center gap-1 rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50 font-bold px-2.5 py-1 text-xs';
                      b.textContent = '💰 예치금 충전' + (row.charge_suffix || '');
                      f.appendChild(b); cw.appendChild(f);
                  }
                  wrap.appendChild(cw);
              }
              return wrap;
          } },
    ], @json($gridRows));

    // ── 일괄 매핑 툴바 — 선택 행의 입금자명(중복 제거) 구동 ──
    const bar = document.getElementById('bankBulkBar');
    const bulkForm = document.getElementById('bankBulkForm');
    const cntEl = document.getElementById('bankBulkCount');
    const storeSel = document.getElementById('bankBulkStore');
    const submitBtn = document.getElementById('bankBulkSubmit');
    const gridEl = document.getElementById('hqBankGrid');

    const pickedNames = () => {
        const names = grid.getCheckedRows().map((r) => r.depositor).filter((n) => n);
        return Array.from(new Set(names));
    };
    function refreshBar() {
        const names = pickedNames();
        if (names.length === 0) { bar.classList.add('hidden'); return; }
        bar.classList.remove('hidden');
        cntEl.textContent = names.length;
    }
    gridEl.addEventListener('change', refreshBar);
    storeSel.addEventListener('change', function () { submitBtn.disabled = !storeSel.value; });

    document.getElementById('bankBulkClear').addEventListener('click', function () {
        gridEl.querySelectorAll('input[type="checkbox"]:checked').forEach((c) => {
            c.checked = false;
            c.dispatchEvent(new Event('change', { bubbles: true }));
        });
        refreshBar();
    });

    bulkForm.addEventListener('submit', function () {
        bulkForm.querySelectorAll('input[name="depositor_names[]"]').forEach((n) => n.remove());
        pickedNames().forEach((name) => bulkForm.appendChild(hidden('depositor_names[]', name)));
    });
})();
</script>
@endpush
@endsection
