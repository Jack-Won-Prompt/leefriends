@extends('portal.layout')
@section('title', '휴무 관리')

@section('content')
<div x-data="{ open: false }">
<x-wms.page-head title="휴무 관리" subtitle="휴무를 신청하면 정직원이 승인합니다." icon="🌴">
    <x-slot:actions>
        <button type="button" @click="open = true" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 휴무 신청</button>
    </x-slot:actions>
</x-wms.page-head>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $leaves->map(fn ($l) => [
        'leave_date' => $l->leave_date->format('Y.m.d (D)'),
        'reason' => $l->reason ?: '-',
        'status' => $l->status,
        'status_label' => $l->statusLabel(),
        'can_cancel' => $l->status !== 'approved',
        'destroy_url' => route('portal.leaves.destroy', $l),
    ])->values();
@endphp

<x-wms.panel>
    <div id="leavesGrid"></div>
</x-wms.panel>

{{-- 휴무 신청 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4 overflow-y-auto" @click.self="open = false">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto my-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900">휴무 신청</h2>
            <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" action="{{ route('portal.leaves.store') }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">휴무 날짜 *</label>
                <input type="date" name="leave_date" required min="{{ now()->format('Y-m-d') }}"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">사유 <span class="text-neutral-400 font-normal">(선택)</span></label>
                <input type="text" name="reason" maxlength="200"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="개인 사정 등">
            </div>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition">신청</button>
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
        pending: 'bg-amber-100 text-amber-700',
        approved: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-rose-100 text-rose-700',
    };
    ww.grid('leavesGrid', [
        { header: '휴무일', name: 'leave_date', width: 180 },
        { header: '사유', name: 'reason', width: 260 },
        { header: '상태', name: 'status', width: 110,
          renderer: (v, row) => ww.badge(row.status_label, STATUS_CLS[v] || 'bg-neutral-100 text-neutral-500') },
        { header: '관리', name: 'can_cancel', width: 96, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              if (!v) return null;
              const form = document.createElement('form');
              form.method = 'POST'; form.action = row.destroy_url; form.className = 'inline';
              form.addEventListener('submit', (e) => { if (!confirm('휴무 신청을 취소할까요?')) e.preventDefault(); });
              const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
              const m = document.createElement('input'); m.type = 'hidden'; m.name = '_method'; m.value = 'DELETE'; form.appendChild(m);
              const b = document.createElement('button');
              b.type = 'submit'; b.textContent = '취소';
              b.className = 'text-xs text-neutral-400 hover:text-rose-500 font-bold';
              form.appendChild(b);
              return form;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection
