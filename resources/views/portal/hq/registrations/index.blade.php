@extends('portal.layout')
@section('title', '회원가입 승인')

@section('content')
<div x-data="{
        rejectOpen: false,
        rejectAction: '',
        rejectName: '',
        openReject(action, name) { this.rejectAction = action; this.rejectName = name; this.rejectOpen = true; },
     }"
     @reg-reject-open.window="openReject($event.detail.action, $event.detail.name)">

<x-wms.page-head title="회원가입 승인" subtitle="자가 가입한 제품 구매자 · 공급자 신청을 검토하고 승인/반려합니다" icon="📝" />

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $pending->map(function ($u) {
        $org = $u->role === 'store' ? $u->store : $u->supplier;
        return [
            'role' => $u->role,
            'type_label' => $u->signup_type_label,
            'org_name' => $org?->name ?? '-',
            'name_for_action' => $org?->name ?? '',
            'biz_no' => $org?->biz_no,
            'manager' => $u->name,
            'phone' => $u->phone ?: '-',
            'email' => $u->email,
            'created_at' => $u->created_at->format('Y-m-d'),
            'approve_url' => route('portal.hq.registrations.approve', $u),
            'reject_url' => route('portal.hq.registrations.reject', $u),
            'approve_confirm' => ($org?->name ?? '').' 님의 가입을 승인하시겠습니까?',
        ];
    })->values();
@endphp

<x-wms.panel :title="'목록 (합계 '.number_format($pending->total()).'건)'">
    <div id="hqRegistrationsGrid"></div>
</x-wms.panel>

@if ($pending->hasPages())
    <div class="mt-4">{{ $pending->links() }}</div>
@endif

{{-- 반려 사유 입력 모달 --}}
<div x-show="rejectOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="rejectOpen = false">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl p-6">
        <h3 class="text-lg font-extrabold text-neutral-900 mb-1">가입 반려</h3>
        <p class="text-sm text-neutral-500 mb-4"><span class="font-bold" x-text="rejectName"></span> 님의 가입을 반려합니다.</p>
        <form method="POST" :action="rejectAction">
            @csrf
            <label class="block text-sm font-bold text-neutral-700 mb-1.5">반려 사유 <span class="font-normal text-neutral-400">(선택 · 신청자에게 메일로 안내됩니다)</span></label>
            <textarea name="reason" rows="3" maxlength="255"
                      class="w-full rounded-xl border-neutral-200 focus:border-rose-400 focus:ring-rose-400 text-sm"
                      placeholder="예) 사업자 정보 확인이 필요합니다."></textarea>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="rejectOpen = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2 text-sm transition">취소</button>
                <button class="rounded-xl bg-rose-500 hover:bg-rose-600 text-white font-bold px-4 py-2 text-sm transition">반려 처리</button>
            </div>
        </form>
    </div>
</div>

</div>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const ROLE_CLS = { store: 'bg-sky-50 text-sky-600', supplier: 'bg-violet-50 text-violet-600' };
    ww.grid('hqRegistrationsGrid', [
        { header: '회원 종류', name: 'type_label', width: 140,
          renderer: (v, row) => ww.badge((row.role === 'store' ? '🛒 ' : '📦 ') + row.type_label, ROLE_CLS[row.role] || '') },
        { header: '상호', name: 'org_name', width: 200,
          renderer: (v, row) => {
              const d = document.createElement('div');
              const n = document.createElement('div'); n.className = 'font-bold text-neutral-900'; n.textContent = row.org_name; d.appendChild(n);
              if (row.biz_no) { const b = document.createElement('div'); b.className = 'text-xs text-neutral-400'; b.textContent = row.biz_no; d.appendChild(b); }
              return d;
          } },
        { header: '담당자', name: 'manager', width: 110 },
        { header: '연락처', name: 'phone', width: 140 },
        { header: '이메일', name: 'email', width: 200 },
        { header: '신청일', name: 'created_at', width: 120 },
        { header: '처리', name: 'approve_url', width: 160, align: 'center', sortable: false, exportable: false,
          renderer: (v, row) => {
              const wrap = document.createElement('div'); wrap.className = 'flex items-center justify-center gap-2';
              const form = document.createElement('form'); form.method = 'POST'; form.action = row.approve_url;
              form.addEventListener('submit', (e) => { if (!confirm(row.approve_confirm)) e.preventDefault(); });
              const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
              const ab = document.createElement('button'); ab.type = 'submit'; ab.textContent = '승인';
              ab.className = 'rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-3 py-1.5 text-xs transition';
              form.appendChild(ab); wrap.appendChild(form);
              const rb = document.createElement('button'); rb.type = 'button'; rb.textContent = '반려';
              rb.className = 'rounded-lg bg-neutral-100 hover:bg-rose-50 text-neutral-600 hover:text-rose-600 font-bold px-3 py-1.5 text-xs transition';
              rb.addEventListener('click', () => window.dispatchEvent(new CustomEvent('reg-reject-open', { detail: { action: row.reject_url, name: row.name_for_action } })));
              wrap.appendChild(rb);
              return wrap;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection
