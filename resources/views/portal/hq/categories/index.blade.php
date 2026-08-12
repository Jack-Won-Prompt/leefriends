@extends('portal.layout')
@section('title', '카테고리 관리')

@section('content')
<div x-data="{
        open: false, mode: 'create',
        form: { id: null, name: '', code: '', sort_order: 0 },
        openCreate() { this.mode = 'create'; this.form = { id: null, name: '', code: '', sort_order: 0 }; this.open = true; },
        openEdit(c) { this.mode = 'edit'; this.form = { id: c.id, name: c.name, code: c.code, sort_order: c.sort_order }; this.open = true; },
        action() { return this.mode === 'create' ? '{{ route('portal.hq.categories.store') }}' : '{{ url('portal/hq/categories') }}/' + this.form.id; },
     }"
     @cat-edit-open.window="openEdit($event.detail)"
     @if ($errors->any()) x-init="open = true; mode = '{{ old('id') ? 'edit' : 'create' }}'; form = { id: '{{ old('id') }}', name: '{{ old('name') }}', code: '{{ old('code') }}', sort_order: {{ (int) old('sort_order', 0) }} }" @endif>

<x-wms.page-head title="카테고리 관리" subtitle="품목 대분류(카테고리)를 추가·수정·정렬합니다. 정렬 순서는 카탈로그/발주 화면에 반영됩니다." icon="🗂️">
    <x-slot:actions>
        <button type="button" @click="openCreate()" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 카테고리 추가</button>
    </x-slot:actions>
</x-wms.page-head>

@error('category')<div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-5 py-3 text-sm">{{ $message }}</div>@enderror

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $categories->map(fn ($c) => [
        'sort_order' => (int) $c->sort_order,
        'name' => $c->name,
        'code' => $c->code,
        'product_count' => (int) $c->product_count,
        'destroy_url' => route('portal.hq.categories.destroy', $c),
        'edit' => ['id' => $c->id, 'name' => $c->name, 'code' => $c->code, 'sort_order' => (int) $c->sort_order],
    ])->values();
@endphp

<x-wms.panel>
    <div id="hqCategoriesGrid"></div>
</x-wms.panel>

{{-- 추가/수정 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="open = false">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900" x-text="mode === 'create' ? '카테고리 추가' : '카테고리 수정'"></h2>
            <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" :action="action()" class="p-5 space-y-4">
            @csrf
            <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PATCH"></template>
            <input type="hidden" name="id" :value="form.id">
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">카테고리명 *</label>
                <input type="text" name="name" x-model="form.name" required maxlength="50"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="예: 음료">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">코드 <span class="text-neutral-400 font-normal">(영문/숫자)</span></label>
                    <input type="text" name="code" x-model="form.code" maxlength="10"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm uppercase" placeholder="자동생성">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">정렬 순서</label>
                    <input type="number" name="sort_order" x-model.number="form.sort_order"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
            </div>
            <p class="text-[11px] text-neutral-400">코드는 품목코드 접두로 사용됩니다(예: MAC001). 수정 시 소속 품목의 분류·코드도 함께 갱신됩니다.</p>
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
    ww.grid('hqCategoriesGrid', [
        { header: '순서', name: 'sort_order', width: 80, align: 'right' },
        { header: '카테고리명', name: 'name', width: 220 },
        { header: '코드', name: 'code', width: 120,
          renderer: (v) => { const s = document.createElement('span'); s.className = 'font-mono text-xs font-bold px-2 py-1 rounded bg-neutral-100 text-neutral-600'; s.textContent = v; return s; } },
        { header: '품목 수', name: 'product_count', width: 100, align: 'right', renderer: (v) => ww.num(v) + '개' },
        { header: '관리', name: 'destroy_url', width: 120, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              const wrap = document.createElement('div'); wrap.className = 'flex items-center justify-end gap-2';
              const eb = document.createElement('button'); eb.type = 'button'; eb.textContent = '수정';
              eb.className = 'text-mango-600 hover:text-mango-700 text-xs font-bold';
              eb.addEventListener('click', () => window.dispatchEvent(new CustomEvent('cat-edit-open', { detail: row.edit })));
              wrap.appendChild(eb);
              const form = document.createElement('form'); form.method = 'POST'; form.action = row.destroy_url;
              form.addEventListener('submit', (e) => { if (!confirm('이 카테고리를 삭제할까요?')) e.preventDefault(); });
              const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
              const m = document.createElement('input'); m.type = 'hidden'; m.name = '_method'; m.value = 'DELETE'; form.appendChild(m);
              const db = document.createElement('button'); db.type = 'submit'; db.textContent = '삭제';
              db.className = 'text-rose-500 hover:text-rose-600 text-xs font-bold';
              if (row.product_count > 0) { db.disabled = true; db.title = '품목이 있어 삭제 불가'; db.classList.add('opacity-30', 'cursor-not-allowed'); }
              form.appendChild(db); wrap.appendChild(form);
              return wrap;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection
