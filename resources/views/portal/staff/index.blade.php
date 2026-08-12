@extends('portal.layout')
@section('title', '직원 관리')

@section('content')
@php($meId = auth()->id())
<div x-data="{
        open: false, mode: 'create',
        form: { id: null, name: '', email: '', phone: '', password: '', employment_type: 'regular', hourly_wage: '' },
        openCreate() { this.mode = 'create'; this.form = { id: null, name: '', email: '', phone: '', password: '', employment_type: 'regular', hourly_wage: '' }; this.open = true; },
        openEdit(u) { this.mode = 'edit'; this.form = { id: u.id, name: u.name, email: u.email, phone: u.phone || '', password: '', employment_type: u.employment_type || 'regular', hourly_wage: u.hourly_wage || '' }; this.open = true; },
        action() { return this.mode === 'create' ? '{{ route('portal.staff.store') }}' : '{{ url('portal/staff') }}/' + this.form.id; },
     }"
     @staff-edit-open.window="openEdit($event.detail)">

<x-wms.page-head title="직원 관리" subtitle="우리 조직 소속 직원의 로그인 계정을 등록·관리합니다. 이메일이 로그인 ID입니다." icon="👥">
    <x-slot:actions>
        <button type="button" @click="openCreate()" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 직원 등록</button>
    </x-slot:actions>
</x-wms.page-head>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $staff->map(fn ($u) => [
        'name' => $u->name,
        'is_me' => $u->id === $meId,
        'email' => $u->email,
        'employment_type' => $u->employment_type,
        'is_part' => $u->employment_type === 'part_time',
        'hourly_wage' => (int) $u->hourly_wage,
        'phone' => $u->phone ?: '-',
        'created_at' => $u->created_at->format('Y.m.d'),
        'edit' => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone, 'employment_type' => $u->employment_type, 'hourly_wage' => (int) $u->hourly_wage],
        'destroy_url' => route('portal.staff.destroy', $u),
        'can_delete' => $u->id !== $meId,
    ])->values();
@endphp

<x-wms.panel>
    <div id="staffGrid"></div>
</x-wms.panel>

{{-- 추가/수정 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="open = false">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900" x-text="mode === 'create' ? '직원 등록' : '직원 수정'"></h2>
            <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" :action="action()" class="p-5 space-y-4">
            @csrf
            <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PATCH"></template>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">이름 *</label>
                <input type="text" name="name" x-model="form.name" required maxlength="50"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="홍길동">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">이메일 (로그인 ID) *</label>
                <input type="email" name="email" x-model="form.email" required maxlength="100"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="staff@example.com">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">
                    <span x-text="mode === 'create' ? '임시 비밀번호 *' : '비밀번호 재설정'"></span>
                    <span class="text-neutral-400 font-normal" x-show="mode === 'edit'">(변경 시에만 입력)</span>
                </label>
                <input type="text" name="password" x-model="form.password" :required="mode === 'create'" minlength="4" maxlength="100" autocomplete="new-password"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="임시 비밀번호">
                <p class="text-[11px] text-neutral-400 mt-1">직원에게 전달 후 로그인하여 변경하도록 안내하세요.</p>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">휴대폰 번호 <span class="text-neutral-400 font-normal">(선택)</span></label>
                <input type="text" name="phone" x-model="form.phone" maxlength="30"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="010-0000-0000">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">구분 *</label>
                <select name="employment_type" x-model="form.employment_type" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    <option value="regular">정직원</option>
                    <option value="part_time">아르바이트</option>
                </select>
            </div>
            <div x-show="form.employment_type === 'part_time'" x-cloak>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">시급 (원) *</label>
                <input type="number" name="hourly_wage" x-model.number="form.hourly_wage" min="0" max="1000000" step="10"
                       :required="form.employment_type === 'part_time'"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="예: 10030">
                <p class="text-[11px] text-neutral-400 mt-1">아르바이트는 로그인 시 출근관리(출퇴근·휴무)만 이용합니다.</p>
            </div>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition" x-text="mode === 'create' ? '등록' : '저장'"></button>
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
    ww.grid('staffGrid', [
        { header: '이름', name: 'name', width: 170,
          renderer: (v, row) => {
              const wrap = ww.el('span', 'font-bold text-neutral-900'); wrap.textContent = v;
              if (row.is_me) wrap.appendChild(ww.el('span', 'ml-1 text-[11px] font-bold px-1.5 py-0.5 rounded bg-mango-100 text-mango-700', '나'));
              return wrap;
          } },
        { header: '이메일 (로그인 ID)', name: 'email', width: 220 },
        { header: '구분', name: 'employment_type', width: 110, align: 'center',
          renderer: (v) => v === 'part_time' ? ww.badge('아르바이트', 'bg-sky-100 text-sky-700') : ww.badge('정직원', 'bg-neutral-100 text-neutral-500') },
        { header: '시급', name: 'hourly_wage', width: 110, align: 'right',
          renderer: (v, row) => row.is_part ? ww.num(v) + '원' : '-' },
        { header: '휴대폰', name: 'phone', width: 150 },
        { header: '등록일', name: 'created_at', width: 120 },
        { header: '관리', name: 'destroy_url', width: 130, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              const wrap = ww.el('div', 'flex items-center justify-end gap-2');
              const eb = ww.el('button', 'text-mango-600 hover:text-mango-700 text-xs font-bold', '수정'); eb.type = 'button';
              eb.addEventListener('click', () => window.dispatchEvent(new CustomEvent('staff-edit-open', { detail: row.edit })));
              wrap.appendChild(eb);
              if (row.can_delete) {
                  const form = document.createElement('form'); form.method = 'POST'; form.action = row.destroy_url;
                  form.addEventListener('submit', (e) => { if (!confirm('«' + row.name + '» 직원 계정을 삭제할까요? 로그인이 즉시 차단됩니다.')) e.preventDefault(); });
                  const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
                  const m = document.createElement('input'); m.type = 'hidden'; m.name = '_method'; m.value = 'DELETE'; form.appendChild(m);
                  const db = ww.el('button', 'text-rose-500 hover:text-rose-600 text-xs font-bold', '삭제'); db.type = 'submit'; form.appendChild(db);
                  wrap.appendChild(form);
              }
              return wrap;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection
