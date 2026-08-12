@extends('portal.layout')
@section('title', '택배사 관리')

@section('content')
<div x-data="{
        open: false, mode: 'create',
        form: { id: null, name: '', is_direct: false, is_active: true, sort_order: 0 },
        openCreate() { this.mode = 'create'; this.form = { id: null, name: '', is_direct: false, is_active: true, sort_order: 0 }; this.open = true; },
        openEdit(c) { this.mode = 'edit'; this.form = Object.assign({}, c); this.open = true; },
        action() { return this.mode === 'create' ? '{{ route('portal.hq.couriers.store') }}' : '{{ url('portal/hq/couriers') }}/' + this.form.id; },
     }"
     @courier-edit-open.window="openEdit($event.detail)">

<x-wms.page-head title="택배사 관리" subtitle="출고 확정 시 선택할 택배사를 등록·관리합니다. 직접 배송은 송장번호 없이 출고됩니다." icon="🚚">
    <x-slot:actions>
        <button type="button" @click="openCreate()" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 택배사 추가</button>
    </x-slot:actions>
</x-wms.page-head>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $couriers->map(fn ($c) => [
        'sort_order' => (int) $c->sort_order,
        'name' => $c->name,
        'is_direct' => (bool) $c->is_direct,
        'is_active' => (bool) $c->is_active,
        'destroy_url' => route('portal.hq.couriers.destroy', $c),
        'edit' => ['id' => $c->id, 'name' => $c->name, 'is_direct' => (bool) $c->is_direct, 'is_active' => (bool) $c->is_active, 'sort_order' => (int) $c->sort_order],
    ])->values();
@endphp

<x-wms.panel>
    <div id="hqCouriersGrid"></div>
</x-wms.panel>

{{-- 추가/수정 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="open = false">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900" x-text="mode === 'create' ? '택배사 추가' : '택배사 수정'"></h2>
            <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" :action="action()" class="p-5 space-y-4">
            @csrf
            <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PATCH"></template>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">택배사명 *</label>
                <input type="text" name="name" x-model="form.name" required maxlength="50"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="예: CJ대한통운">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <label class="flex items-center gap-2 rounded-xl border border-neutral-200 px-3 py-2.5">
                    <input type="checkbox" name="is_direct" value="1" x-model="form.is_direct" class="rounded text-sky-500 focus:ring-sky-400">
                    <span class="text-sm font-semibold text-neutral-700">직접 배송 <span class="text-neutral-400 font-normal">(송장 불필요)</span></span>
                </label>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">정렬 순서</label>
                    <input type="number" name="sort_order" x-model.number="form.sort_order"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
            </div>
            <template x-if="mode === 'edit'">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded text-mango-500 focus:ring-mango-400">
                    <span class="text-sm font-semibold text-neutral-700">사용 (출고 폼에 노출)</span>
                </label>
            </template>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition" x-text="mode === 'create' ? '추가' : '저장'"></button>
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
    ww.grid('hqCouriersGrid', [
        { header: '순서', name: 'sort_order', width: 80, align: 'right' },
        { header: '택배사', name: 'name', width: 220 },
        { header: '구분', name: 'is_direct', width: 120, align: 'center',
          renderer: (v) => v ? ww.badge('직접 배송', 'bg-sky-100 text-sky-700') : ww.badge('택배', 'bg-neutral-100 text-neutral-600') },
        { header: '상태', name: 'is_active', width: 120, align: 'center',
          renderer: (v) => v ? ww.badge('사용', 'bg-emerald-100 text-emerald-700') : ww.badge('미사용', 'bg-neutral-100 text-neutral-400') },
        { header: '관리', name: 'destroy_url', width: 120, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              const wrap = document.createElement('div'); wrap.className = 'flex items-center justify-end gap-2';
              const eb = document.createElement('button'); eb.type = 'button'; eb.textContent = '수정';
              eb.className = 'text-mango-600 hover:text-mango-700 text-xs font-bold';
              eb.addEventListener('click', () => window.dispatchEvent(new CustomEvent('courier-edit-open', { detail: row.edit })));
              wrap.appendChild(eb);
              const form = document.createElement('form'); form.method = 'POST'; form.action = row.destroy_url;
              form.addEventListener('submit', (e) => { if (!confirm('이 택배사를 삭제할까요?')) e.preventDefault(); });
              const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
              const m = document.createElement('input'); m.type = 'hidden'; m.name = '_method'; m.value = 'DELETE'; form.appendChild(m);
              const db = document.createElement('button'); db.type = 'submit'; db.textContent = '삭제';
              db.className = 'text-rose-500 hover:text-rose-600 text-xs font-bold'; form.appendChild(db);
              wrap.appendChild(form);
              return wrap;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection
