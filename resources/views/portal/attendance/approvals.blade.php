@extends('portal.layout')
@section('title', '출근 승인')

@section('content')
<div x-data="{ regOpen: false, uid: '' }">
<x-wms.page-head title="출근 승인" subtitle="출근·퇴근 시간을 확인하고 승인합니다. 정직원·아르바이트 출퇴근을 직접 등록할 수도 있습니다." icon="✅" />

{{-- 필터 --}}
<form method="GET" action="{{ route('portal.attendance.approvals') }}" class="flex flex-wrap items-end gap-3 mb-5">
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">상태</label>
        <select name="status" class="rounded-xl border-neutral-200 text-sm py-2">
            <option value="all" @selected($status==='all')>전체</option>
            <option value="pending" @selected($status==='pending')>승인대기</option>
            <option value="approved" @selected($status==='approved')>승인</option>
            <option value="rejected" @selected($status==='rejected')>반려</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">직원</label>
        <select name="user" class="rounded-xl border-neutral-200 text-sm py-2 min-w-[9rem]">
            <option value="">전체</option>
            @foreach ($staff as $p)
                <option value="{{ $p->id }}" @selected((int)$userId === $p->id)>{{ $p->name }} ({{ $p->employment_type === 'regular' ? '정직원' : '아르바이트' }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">시작일</label>
        <input type="date" name="from" value="{{ $from }}" class="rounded-xl border-neutral-200 text-sm py-2">
    </div>
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">종료일</label>
        <input type="date" name="to" value="{{ $to }}" class="rounded-xl border-neutral-200 text-sm py-2">
    </div>
    <button type="submit" class="rounded-xl bg-neutral-800 hover:bg-neutral-900 text-white font-bold px-4 py-2.5 text-sm transition">조회</button>
    <a href="{{ route('portal.attendance.approvals') }}" class="rounded-xl border border-neutral-200 hover:bg-neutral-50 text-neutral-500 font-bold px-4 py-2.5 text-sm">초기화</a>
    <button type="button" @click="regOpen = true" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition">＋ 출퇴근 등록</button>
</form>

@include('portal.partials.wwgrid-assets')
@php
    $attRows = $attendances->map(fn ($a) => [
        'id' => $a->id,
        'user_name' => $a->user->name ?? '-',
        'work_date' => $a->work_date->format('m.d (D)'),
        'clock_in' => $a->clock_in_at->format('H:i'),
        'clock_out' => $a->clock_out_at ? $a->clock_out_at->format('H:i') : '근무중',
        'hours' => $a->clock_out_at ? $a->hours().'시간' : '-',
        'status' => $a->status,
        'status_label' => $a->statusLabel(),
        'approvable' => $a->status === 'pending' && (bool) $a->clock_out_at,
        'is_pending' => $a->status === 'pending',
        'approve_url' => route('portal.attendance.approve', $a),
        'reject_url' => route('portal.attendance.reject', $a),
    ])->values();
    $staffRows = collect($staff)->map(fn ($u) => [
        'name' => $u->name,
        'is_regular' => $u->employment_type === 'regular',
        'emp_label' => $u->employment_type === 'regular' ? '정직원' : '아르바이트',
        'pay' => $u->employment_type === 'regular'
            ? number_format((int) $u->monthly_salary).'원/월'
            : number_format((int) $u->hourly_wage).'원/시',
        'work' => $u->employment_type === 'regular' ? (($u->work_start ?: '09:00').' ~ '.($u->work_end ?: '18:00')) : '—',
        'manage_url' => route('portal.attendance.manage', $u->id),
    ])->values();
    $leaveRows = $leaves->map(fn ($l) => [
        'id' => $l->id,
        'user_name' => $l->user->name ?? '-',
        'leave_date' => $l->leave_date->format('m.d (D)'),
        'reason' => $l->reason ?: '-',
        'status' => $l->status,
        'status_label' => $l->statusLabel(),
        'is_pending' => $l->status === 'pending',
        'approve_url' => route('portal.leaves.approve', $l),
        'reject_url' => route('portal.leaves.reject', $l),
    ])->values();
@endphp

{{-- 출퇴근 / 휴무 탭 --}}
<div class="flex items-center gap-1 border-b border-neutral-200 mb-2">
    <button type="button" id="attTabStaff" onclick="attTab('staff')"
            class="px-4 py-2.5 text-sm font-extrabold border-b-2 border-transparent text-neutral-400 -mb-px transition">👥 직원 <span class="font-bold text-neutral-300">({{ number_format(count($staff)) }})</span></button>
    <button type="button" id="attTabWork" onclick="attTab('work')"
            class="px-4 py-2.5 text-sm font-extrabold border-b-2 border-mango-500 text-mango-600 -mb-px transition">🕐 출퇴근 <span class="font-bold text-neutral-400">({{ number_format($attendances->total()) }})</span></button>
    <button type="button" id="attTabLeave" onclick="attTab('leave')"
            class="px-4 py-2.5 text-sm font-extrabold border-b-2 border-transparent text-neutral-400 -mb-px transition">🌴 휴무 <span class="font-bold text-neutral-300">({{ number_format($leaves->total()) }})</span></button>
</div>

<div id="attPaneStaff" class="hidden">
    <x-wms.panel>
        <div id="staffOverviewGrid"></div>
    </x-wms.panel>
</div>

<div id="attPaneWork">
    {{-- 출퇴근 일괄 승인 툴바 --}}
    <div id="attBulkBar" class="mb-3 flex items-center gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 hidden">
        <span class="text-sm font-bold text-emerald-800">선택 <span id="attBulkCount"></span>건</span>
        <form id="attBulkForm" method="POST" action="{{ route('portal.attendance.bulk_approve') }}" onsubmit="return confirm('선택한 출퇴근 기록을 일괄 승인할까요?')">
            @csrf
            <button type="submit" class="rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-4 py-2 text-sm transition">✅ 선택 승인</button>
        </form>
        <button type="button" id="attBulkClear" class="text-xs text-neutral-500 hover:underline">선택 해제</button>
    </div>
    <x-wms.panel>
        <div id="attGrid"></div>
        @if ($attendances->hasPages())
            <div class="px-5 py-3 border-t border-neutral-100">{{ $attendances->links() }}</div>
        @endif
    </x-wms.panel>
</div>

<div id="attPaneLeave" class="hidden">
    <x-wms.panel>
        <div id="leaveGrid"></div>
        @if ($leaves->hasPages())
            <div class="px-5 py-3 border-t border-neutral-100">{{ $leaves->links() }}</div>
        @endif
    </x-wms.panel>
</div>

{{-- 출퇴근 등록 모달 (정직원·아르바이트 공통) --}}
<div x-show="regOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="regOpen = false">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6" @click.outside="regOpen = false">
        <h3 class="text-lg font-extrabold text-neutral-900 mb-4">🕐 출퇴근 등록</h3>
        <form :action="'{{ url('portal/attendance/manage') }}/' + uid" method="POST"
              @submit="if (!uid) { $event.preventDefault(); alert('직원을 선택해 주세요.'); }">
            @csrf
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">직원 *</label>
                    <select x-model="uid" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                        <option value="">직원을 선택하세요</option>
                        @foreach ($staff as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->employment_type === 'regular' ? '정직원' : '아르바이트' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">근무일 *</label>
                    <input type="date" name="work_date" value="{{ now()->format('Y-m-d') }}" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-1.5">출근 *</label>
                        <input type="time" name="clock_in" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-1.5">퇴근</label>
                        <input type="time" name="clock_out" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    </div>
                </div>
                <p class="text-[11px] text-neutral-400">퇴근 시간까지 입력하면 자동으로 승인 처리됩니다.</p>
                <div class="flex gap-2 pt-1">
                    <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm">등록</button>
                    <button type="button" @click="regOpen = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>{{-- /x-data --}}

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const STATUS_CLS = {
        pending: 'bg-amber-100 text-amber-700',
        approved: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-rose-100 text-rose-700',
    };
    const statusBadge = (label, status) =>
        ww.el('span', 'text-xs font-bold px-2 py-0.5 rounded-full ' + (STATUS_CLS[status] || ''), label);

    // PATCH form (승인/반려) 노드 생성
    const patchForm = (url, label, btnCls) => {
        const form = document.createElement('form');
        form.method = 'POST'; form.action = url;
        const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
        const m = document.createElement('input'); m.type = 'hidden'; m.name = '_method'; m.value = 'PATCH'; form.appendChild(m);
        const b = document.createElement('button'); b.type = 'submit'; b.textContent = label; b.className = btnCls; form.appendChild(b);
        return form;
    };
    const APPROVE_CLS = 'rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-3 py-1.5 text-xs';
    const REJECT_CLS = 'rounded-lg border border-neutral-200 hover:bg-neutral-50 text-neutral-500 font-bold px-3 py-1.5 text-xs';

    // ── 직원 개요 그리드 ──
    ww.grid('staffOverviewGrid', [
        { header: '직원', name: 'name', width: 160, renderer: (v) => ww.el('span', 'font-bold text-neutral-900', v) },
        { header: '구분', name: 'emp_label', width: 110, align: 'center',
          renderer: (v, row) => ww.badge(v, row.is_regular ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-700') },
        { header: '급여', name: 'pay', width: 140, align: 'right', renderer: (v) => ww.el('span', 'tabular-nums text-neutral-700', v) },
        { header: '표준근무', name: 'work', width: 150, align: 'center', renderer: (v) => ww.el('span', 'text-neutral-500', v) },
        { header: '출퇴근', name: 'manage_url', width: 150, align: 'right', sortable: false, exportable: false,
          renderer: (v) => { const a = document.createElement('a'); a.href = v; a.textContent = '🕐 출퇴근 관리'; a.className = 'text-xs font-semibold text-mango-600 hover:underline'; return a; } },
    ], @json($staffRows));

    // ── 출퇴근 그리드 ──
    const attGrid = ww.grid('attGrid', [
        { header: '직원', name: 'user_name', width: 130,
          renderer: (v) => ww.el('span', 'font-bold text-neutral-900', v) },
        { header: '근무일', name: 'work_date', width: 120,
          renderer: (v) => ww.el('span', 'text-neutral-600', v) },
        { header: '출근', name: 'clock_in', width: 90,
          renderer: (v) => ww.el('span', 'text-neutral-600', v) },
        { header: '퇴근', name: 'clock_out', width: 90,
          renderer: (v) => ww.el('span', 'text-neutral-600', v) },
        { header: '근무시간', name: 'hours', width: 110, align: 'right',
          renderer: (v) => ww.el('span', 'tabular-nums', v) },
        { header: '상태', name: 'status_label', width: 100,
          renderer: (v, row) => statusBadge(v, row.status) },
        { header: '처리', name: 'id', width: 160, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              if (row.approvable) {
                  const wrap = document.createElement('div');
                  wrap.className = 'flex justify-end gap-1.5';
                  wrap.appendChild(patchForm(row.approve_url, '승인', APPROVE_CLS));
                  wrap.appendChild(patchForm(row.reject_url, '반려', REJECT_CLS));
                  return wrap;
              }
              if (row.is_pending) return ww.el('span', 'text-xs text-neutral-400', '퇴근 전');
              return ww.el('span', 'text-xs text-neutral-400', '처리완료');
          } },
    ], @json($attRows));

    // ── 휴무 그리드 ──
    ww.grid('leaveGrid', [
        { header: '직원', name: 'user_name', width: 130,
          renderer: (v) => ww.el('span', 'font-bold text-neutral-900', v) },
        { header: '휴무일', name: 'leave_date', width: 120,
          renderer: (v) => ww.el('span', 'text-neutral-600', v) },
        { header: '사유', name: 'reason', width: 220,
          renderer: (v) => ww.el('span', 'text-neutral-500', v) },
        { header: '상태', name: 'status_label', width: 100,
          renderer: (v, row) => statusBadge(v, row.status) },
        { header: '처리', name: 'id', width: 160, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              if (row.is_pending) {
                  const wrap = document.createElement('div');
                  wrap.className = 'flex justify-end gap-1.5';
                  wrap.appendChild(patchForm(row.approve_url, '승인', APPROVE_CLS));
                  wrap.appendChild(patchForm(row.reject_url, '반려', REJECT_CLS));
                  return wrap;
              }
              return ww.el('span', 'text-xs text-neutral-400', '처리완료');
          } },
    ], @json($leaveRows));

    // ── 일괄 승인 툴바 — 그리드 선택(승인 가능 건만) 구동 ──
    const bar = document.getElementById('attBulkBar');
    const bulkForm = document.getElementById('attBulkForm');
    const cntEl = document.getElementById('attBulkCount');
    const gridEl = document.getElementById('attGrid');
    const approvable = () => attGrid.getCheckedRows().filter((r) => r.approvable);

    function refreshBar() {
        const rows = approvable();
        if (rows.length === 0) { bar.classList.add('hidden'); return; }
        bar.classList.remove('hidden');
        cntEl.textContent = rows.length;
    }
    gridEl.addEventListener('change', refreshBar);

    document.getElementById('attBulkClear').addEventListener('click', function () {
        gridEl.querySelectorAll('input[type="checkbox"]:checked').forEach((c) => {
            c.checked = false;
            c.dispatchEvent(new Event('change', { bubbles: true }));
        });
        refreshBar();
    });

    bulkForm.addEventListener('submit', function () {
        bulkForm.querySelectorAll('input[name="attendance_ids[]"]').forEach((n) => n.remove());
        approvable().forEach((r) => {
            const i = document.createElement('input');
            i.type = 'hidden'; i.name = 'attendance_ids[]'; i.value = r.id;
            bulkForm.appendChild(i);
        });
    });

    // 직원 / 출퇴근 / 휴무 탭 전환
    window.attTab = function (which) {
        const panes = { staff: 'attPaneStaff', work: 'attPaneWork', leave: 'attPaneLeave' };
        const tabs = { staff: 'attTabStaff', work: 'attTabWork', leave: 'attTabLeave' };
        Object.keys(panes).forEach((k) => {
            document.getElementById(panes[k]).classList.toggle('hidden', k !== which);
            const btn = document.getElementById(tabs[k]);
            const active = k === which;
            btn.classList.toggle('border-mango-500', active);
            btn.classList.toggle('text-mango-600', active);
            btn.classList.toggle('border-transparent', !active);
            btn.classList.toggle('text-neutral-400', !active);
        });
        window.dispatchEvent(new Event('resize'));
    };
})();
</script>
@endpush
@endsection
