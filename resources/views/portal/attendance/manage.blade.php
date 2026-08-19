@extends('portal.layout')
@section('title', $user->name.' 출퇴근 관리')

@section('content')
@php $statusChip = ['pending'=>'bg-amber-100 text-amber-700','approved'=>'bg-emerald-100 text-emerald-700','rejected'=>'bg-rose-100 text-rose-700']; @endphp
<div x-data="{
        open: false,
        form: { id: null, work_date: '', clock_in: '', clock_out: '', approve: false, isNew: false },
        openNew(date) { this.form = { id: null, work_date: date, clock_in: '', clock_out: '', approve: true, isNew: true }; this.open = true; },
        openEdit(rec) { this.form = { id: rec.id, work_date: rec.work_date, clock_in: rec.clock_in, clock_out: rec.clock_out || '', approve: rec.status !== 'approved', isNew: false }; this.open = true; },
        action() { return this.form.isNew ? '{{ route('portal.attendance.manage_store', $user) }}' : '{{ url('portal/attendance') }}/' + this.form.id + '/times'; },
     }"
     @att-mng-edit-open.window="openEdit($event.detail)">

@php
    $payLabel = $user->isRegular()
        ? '월급 '.number_format((int) $user->monthly_salary).'원 · 표준 '.$user->workStart().'~'.$user->workEnd()
        : '시급 '.number_format((int) $user->hourly_wage).'원';
@endphp
<x-wms.page-head :title="$user->name.' 출퇴근 관리'" :subtitle="$payLabel.' · 날짜별 출퇴근 시간 등록·수정·승인'" icon="🕐">
    <x-slot:actions>
        <button type="button" @click="openNew('{{ now()->format('Y-m-d') }}')" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 출퇴근 등록</button>
        <a href="{{ route('portal.wages.index', ['from' => $from, 'to' => $to]) }}"
           class="inline-flex items-center gap-1 rounded-xl border border-neutral-200 hover:bg-neutral-50 font-bold px-4 py-2 text-sm">← 급여</a>
    </x-slot:actions>
</x-wms.page-head>

{{-- 기간 필터 --}}
<form method="GET" class="flex flex-wrap items-end gap-2 mb-4">
    <input type="date" name="from" value="{{ $from }}" class="rounded-xl border-neutral-200 text-sm py-2">
    <span class="pb-2 text-neutral-400">~</span>
    <input type="date" name="to" value="{{ $to }}" class="rounded-xl border-neutral-200 text-sm py-2">
    <button class="rounded-xl bg-neutral-800 hover:bg-neutral-900 text-white font-bold px-4 py-2 text-sm">조회</button>
</form>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $records->map(fn ($a) => [
        'work_date' => $a->work_date->format('Y.m.d (D)'),
        'clock_in' => $a->clock_in_at->format('H:i'),
        'clock_out' => $a->clock_out_at ? $a->clock_out_at->format('H:i') : '—',
        'hours_label' => $a->clock_out_at ? $a->hours().'시간' : '0시간',
        'has_out' => (bool) $a->clock_out_at,
        'wage' => $a->clock_out_at ? number_format($a->wage()).'원' : '-',
        'status' => $a->status,
        'status_label' => $a->statusLabel(),
        'edit' => ['id' => $a->id, 'work_date' => $a->work_date->format('Y-m-d'), 'clock_in' => $a->clock_in_at->format('H:i'), 'clock_out' => $a->clock_out_at?->format('H:i'), 'status' => $a->status],
        'approve_url' => route('portal.attendance.approve', $a),
        'can_approve' => $a->status !== 'approved' && $a->clock_out_at,
    ])->values();
@endphp

<x-wms.panel>
    <div id="attendanceManageGrid"></div>
</x-wms.panel>

{{-- 출퇴근 시간 등록/수정 팝업 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4 overflow-y-auto" @click.self="open = false">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto my-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900" x-text="form.isNew ? '출퇴근 등록' : '출퇴근 시간 수정'"></h2>
            <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" :action="action()" class="p-5 space-y-4">
            @csrf
            <template x-if="! form.isNew"><input type="hidden" name="_method" value="PATCH"></template>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">날짜 *</label>
                <input type="date" name="work_date" x-model="form.work_date" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">출근 *</label>
                    <input type="time" name="clock_in" x-model="form.clock_in" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">퇴근</label>
                    <input type="time" name="clock_out" x-model="form.clock_out" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
            </div>
            <label class="inline-flex items-center gap-1.5 text-sm text-neutral-600">
                <input type="checkbox" name="approve" value="1" x-model="form.approve" class="rounded border-neutral-300 text-emerald-500"> 저장 시 승인 처리
            </label>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition" x-text="form.isNew ? '등록' : '저장'"></button>
                <button type="button" @click="open = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
            </div>
        </form>
    </div>
</div>
</div>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const STATUS_CLS = {
        pending: 'bg-amber-100 text-amber-700', approved: 'bg-emerald-100 text-emerald-700', rejected: 'bg-rose-100 text-rose-700',
    };
    ww.grid('attendanceManageGrid', [
        { header: '날짜', name: 'work_date', width: 160,
          renderer: (v) => ww.el('span', 'font-bold text-neutral-900', v) },
        { header: '출근', name: 'clock_in', width: 100 },
        { header: '퇴근', name: 'clock_out', width: 100 },
        { header: '근무시간', name: 'hours_label', width: 130, sortable: false, exportable: false,
          renderer: (v, row) => {
              const cls = row.has_out ? 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200' : 'bg-amber-100 text-amber-700 hover:bg-amber-200';
              const b = ww.el('button', 'inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-bold ' + cls, '🕐 ' + row.hours_label); b.type = 'button';
              b.addEventListener('click', () => window.dispatchEvent(new CustomEvent('att-mng-edit-open', { detail: row.edit })));
              return b;
          } },
        { header: '일당', name: 'wage', width: 120, align: 'right',
          renderer: (v) => ww.el('span', 'font-semibold', v) },
        { header: '상태', name: 'status', width: 110, align: 'center',
          renderer: (v, row) => ww.badge(row.status_label, STATUS_CLS[v] || 'bg-neutral-100 text-neutral-500') },
        { header: '관리', name: 'approve_url', width: 100, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              if (!row.can_approve) return '';
              const form = document.createElement('form'); form.method = 'POST'; form.action = row.approve_url;
              const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
              const m = document.createElement('input'); m.type = 'hidden'; m.name = '_method'; m.value = 'PATCH'; form.appendChild(m);
              const b = ww.el('button', 'text-emerald-600 hover:underline text-xs font-bold', '승인'); b.type = 'submit'; form.appendChild(b);
              return form;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection
