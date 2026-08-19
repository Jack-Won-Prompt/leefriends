@extends('portal.layout')
@section('title', '직원 급여')

@section('content')
<x-wms.page-head title="직원 급여" subtitle="정직원 월급여(정시 기준 ± 지각·오버타임) · 아르바이트 시급 정산 및 입금 처리" icon="💵" />

{{-- 기간 조회 --}}
<form method="GET" action="{{ route('portal.wages.index') }}" class="flex flex-wrap items-end gap-3 mb-5">
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">시작일</label>
        <input type="date" name="from" value="{{ $from }}" class="rounded-xl border-neutral-200 text-sm py-2">
    </div>
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">종료일</label>
        <input type="date" name="to" value="{{ $to }}" class="rounded-xl border-neutral-200 text-sm py-2">
    </div>
    <button type="submit" class="rounded-xl bg-neutral-800 hover:bg-neutral-900 text-white font-bold px-5 py-2.5 text-sm transition">조회</button>
    <div class="ml-auto text-right">
        <p class="text-xs text-neutral-400">기간 급여 합계</p>
        <p class="text-2xl font-black text-mango-600">{{ number_format($grandAmount) }}<span class="text-base">원</span></p>
    </div>
</form>

@include('portal.partials.wwgrid-assets')
@php
    $mk = function ($r) use ($from, $to) {
        $u = $r['user'];
        $paid = $r['settlement'] && $r['settlement']->status === 'paid';
        return [
            'user_name' => $u->name,
            'manage_url' => route('portal.attendance.manage', ['user' => $u->id, 'from' => $from, 'to' => $to]),
            'days' => (int) $r['days'],
            'amount' => (int) $r['amount'],
            'paid' => $paid,
            'paid_at' => $paid && $r['settlement']->paid_at ? $r['settlement']->paid_at->format('m.d H:i') : null,
            'user_id' => $u->id,
            'hours' => $r['hours'] ?? 0,
            'pay_confirm' => $u->name.'님 급여 '.number_format($r['amount']).'원을 입금 처리합니다.\n(직원에게 알림 전송)\n진행할까요?',
            'unpay_url' => $r['settlement'] ? route('portal.wages.unpay', $r['settlement']) : null,
        ];
    };
    $regRows = collect($regularRows)->map(fn ($r) => array_merge($mk($r), [
        'base' => (int) $r['base'],
        'late_min' => (int) $r['late_min'],
        'ot_min' => (int) $r['ot_min'],
        'late_deduct' => (int) $r['late_deduct'],
        'ot_add' => (int) $r['ot_add'],
    ]))->values();
    $partRows = collect($rows)->map(fn ($r) => array_merge($mk($r), [
        'hourly_wage' => (int) $r['user']->hourly_wage,
    ]))->values();
@endphp

{{-- 정직원 / 아르바이트 탭 --}}
<div class="inline-flex items-center gap-0.5 p-0.5 rounded-lg bg-neutral-200/80 mb-3">
    <button type="button" id="wagesTabReg" onclick="wagesTab('reg')"
            class="inline-flex items-center h-8 px-3.5 rounded-md text-sm font-bold bg-white text-neutral-900 shadow-sm transition">🧑‍💼 정직원 <span class="ml-1 font-bold text-neutral-400">({{ number_format(count($regularRows)) }})</span></button>
    <button type="button" id="wagesTabPart" onclick="wagesTab('part')"
            class="inline-flex items-center h-8 px-3.5 rounded-md text-sm font-bold text-neutral-500 transition">🕐 아르바이트 <span class="ml-1 font-bold text-neutral-400">({{ number_format(count($rows)) }})</span></button>
</div>

<div id="wagesPaneReg">
    <p class="mb-2 text-xs text-neutral-400">월 정액 급여 · 정시 출·퇴근 시 정액, 지각 차감 · 오버타임 ×1.5 가산</p>
    <x-wms.panel>
        <div id="wagesRegularGrid"></div>
    </x-wms.panel>
</div>

<div id="wagesPanePart" class="hidden">
    <p class="mb-2 text-xs text-neutral-400">시급 × 근무시간</p>
    <x-wms.panel>
        <div id="wagesGrid"></div>
    </x-wms.panel>
</div>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const PAY_URL = '{{ route('portal.wages.pay') }}';
    const FROM = '{{ $from }}';
    const TO = '{{ $to }}';
    const hidden = (name, value) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = name; i.value = value; return i; };

    // 입금/취소 처리 셀 (정직원·아르바이트 공용)
    const actionCell = (v, row) => {
        if (row.amount > 0 && !row.paid) {
            const form = document.createElement('form');
            form.method = 'POST'; form.action = PAY_URL;
            form.addEventListener('submit', (e) => { if (!confirm(row.pay_confirm)) e.preventDefault(); });
            form.appendChild(hidden('_token', CSRF));
            form.appendChild(hidden('user_id', row.user_id));
            form.appendChild(hidden('from', FROM));
            form.appendChild(hidden('to', TO));
            form.appendChild(hidden('hours', row.hours || 0));
            form.appendChild(hidden('amount', row.amount));
            const b = document.createElement('button');
            b.type = 'submit'; b.textContent = '💰 입금 처리';
            b.className = 'rounded-lg bg-mango-500 hover:bg-mango-600 text-white font-bold px-3 py-1.5 text-xs';
            form.appendChild(b);
            return form;
        }
        if (row.paid) {
            const form = document.createElement('form');
            form.method = 'POST'; form.action = row.unpay_url;
            form.addEventListener('submit', (e) => { if (!confirm('입금 처리를 취소할까요?')) e.preventDefault(); });
            form.appendChild(hidden('_token', CSRF));
            form.appendChild(hidden('_method', 'DELETE'));
            const b = document.createElement('button');
            b.type = 'submit'; b.textContent = '입금취소';
            b.className = 'text-xs text-neutral-400 hover:text-rose-500 font-bold';
            form.appendChild(b);
            return form;
        }
        const s = document.createElement('span'); s.className = 'text-xs text-neutral-300'; s.textContent = '근무 없음';
        return s;
    };

    const paidCell = (v, row) => {
        const wrap = document.createElement('div');
        if (v) {
            wrap.appendChild(ww.badge('💰 입금완료', 'bg-emerald-100 text-emerald-700'));
            if (row.paid_at) { const s = document.createElement('span'); s.className = 'block text-[11px] text-neutral-400'; s.textContent = row.paid_at; wrap.appendChild(s); }
        } else {
            wrap.appendChild(ww.badge('미입금', 'bg-neutral-100 text-neutral-400'));
        }
        return wrap;
    };

    const nameCell = (v, row) => {
        const wrap = document.createElement('div');
        const nm = document.createElement('div'); nm.className = 'font-bold text-neutral-900'; nm.textContent = row.user_name; wrap.appendChild(nm);
        const a = document.createElement('a'); a.href = row.manage_url; a.textContent = '🕐 출퇴근 관리';
        a.className = 'block text-xs font-semibold text-mango-600 hover:underline mt-0.5'; wrap.appendChild(a);
        return wrap;
    };

    const adj = (min, won, sign, cls) => {
        const wrap = document.createElement('div');
        if (!min) { const s = document.createElement('span'); s.className = 'text-neutral-300'; s.textContent = '—'; return s; }
        const t = document.createElement('div'); t.className = 'text-neutral-600'; t.textContent = min + '분'; wrap.appendChild(t);
        const m = document.createElement('div'); m.className = cls + ' text-xs font-bold'; m.textContent = sign + ww.num(won) + '원'; wrap.appendChild(m);
        return wrap;
    };

    // 정직원
    ww.grid('wagesRegularGrid', [
        { header: '정직원', name: 'user_name', width: 170, renderer: nameCell },
        { header: '월 급여', name: 'base', width: 120, align: 'right', renderer: (v) => ww.won(v) },
        { header: '근무일', name: 'days', width: 80, align: 'right', renderer: (v) => ww.num(v) + '일' },
        { header: '지각', name: 'late_min', width: 120, align: 'right', renderer: (v, row) => adj(row.late_min, row.late_deduct, '−', 'text-rose-600') },
        { header: '오버타임', name: 'ot_min', width: 120, align: 'right', renderer: (v, row) => adj(row.ot_min, row.ot_add, '+', 'text-sky-600') },
        { header: '지급액', name: 'amount', width: 130, align: 'right', renderer: (v) => ww.el('span', 'font-black text-mango-700', ww.won(v)) },
        { header: '입금', name: 'paid', width: 120, renderer: paidCell },
        { header: '처리', name: 'action', width: 150, align: 'right', sortable: false, exportable: false, renderer: actionCell },
    ], @json($regRows));

    // 아르바이트
    ww.grid('wagesGrid', [
        { header: '아르바이트', name: 'user_name', width: 170, renderer: nameCell },
        { header: '시급', name: 'hourly_wage', width: 110, align: 'right', renderer: (v) => ww.won(v) },
        { header: '근무일', name: 'days', width: 90, align: 'right', renderer: (v) => ww.num(v) + '일' },
        { header: '근무시간', name: 'hours', width: 100, align: 'right', renderer: (v) => v + '시간' },
        { header: '급여(일당 합계)', name: 'amount', width: 140, align: 'right', renderer: (v) => ww.won(v) },
        { header: '입금', name: 'paid', width: 130, renderer: paidCell },
        { header: '처리', name: 'action', width: 160, align: 'right', sortable: false, exportable: false, renderer: actionCell },
    ], @json($partRows));

    // 정직원 / 아르바이트 탭 전환
    window.wagesTab = function (which) {
        const reg = which === 'reg';
        document.getElementById('wagesPaneReg').classList.toggle('hidden', !reg);
        document.getElementById('wagesPanePart').classList.toggle('hidden', reg);
        const setActive = (btn, active) => {
            btn.classList.toggle('bg-white', active);
            btn.classList.toggle('text-neutral-900', active);
            btn.classList.toggle('shadow-sm', active);
            btn.classList.toggle('text-neutral-500', !active);
        };
        setActive(document.getElementById('wagesTabReg'), reg);
        setActive(document.getElementById('wagesTabPart'), !reg);
        // 숨겨졌던 그리드 fit 높이 재계산
        window.dispatchEvent(new Event('resize'));
    };
})();
</script>
@endpush
@endsection
