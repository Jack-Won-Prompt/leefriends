@extends('portal.layout')
@section('title', '과일 보관 관리')

@section('content')
@php
    $blank = ['id' => null, 'name' => '', 'temp_c' => '', 'temp_f' => '', 'ventilation' => '', 'humidity' => '', 'dehumidification' => '끔', 'storage_period' => '', 'note' => '', 'sort_order' => 0, 'is_shared' => false, 'is_active' => true];
@endphp
<div x-data="{
        open: false, mode: 'create',
        form: {{ \Illuminate\Support\Js::from($blank) }},
        openCreate() { this.mode = 'create'; this.form = {{ \Illuminate\Support\Js::from($blank) }}; this.open = true; },
        openEdit(f) { this.mode = 'edit'; this.form = Object.assign({}, f); this.open = true; },
        action() { return this.mode === 'create' ? '{{ route('portal.hq.fruit_storages.store') }}' : '{{ url('portal/hq/fruit-storages') }}/' + this.form.id; },
     }"
     @fruit-edit-open.window="openEdit($event.detail)">

<x-wms.page-head title="과일 보관 관리" subtitle="과일·채소의 냉장/냉동 보관 가이드(ZIM 권장)입니다. ‘매장 공유’를 체크하면 매장 포털에서 열람할 수 있습니다." icon="🧊">
    <x-slot:actions>
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ $q }}" placeholder="제품 검색" class="rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm py-2">
            <button class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-3.5 py-2 text-sm">검색</button>
        </form>
        <button type="button" @click="openCreate()" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 항목 추가</button>
    </x-slot:actions>
</x-wms.page-head>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $fruits->map(fn ($f) => [
        'name' => $f->name,
        'temp_c' => $f->temp_c,
        'temp_f' => $f->temp_f,
        'ventilation' => $f->ventilation,
        'humidity' => $f->humidity,
        'dehumidification' => $f->dehumidification,
        'storage_period' => $f->storage_period,
        'is_shared' => (bool) $f->is_shared,
        'toggle_url' => route('portal.hq.fruit_storages.toggle_share', $f),
        'destroy_url' => route('portal.hq.fruit_storages.destroy', $f),
        'edit' => [
            'id' => $f->id, 'name' => $f->name, 'temp_c' => $f->temp_c, 'temp_f' => $f->temp_f,
            'ventilation' => $f->ventilation, 'humidity' => $f->humidity, 'dehumidification' => $f->dehumidification,
            'storage_period' => $f->storage_period, 'note' => $f->note, 'sort_order' => (int) $f->sort_order,
            'is_shared' => (bool) $f->is_shared, 'is_active' => (bool) $f->is_active,
        ],
    ])->values();
@endphp

<x-wms.panel>
    <div id="hqFruitStoragesGrid"></div>
</x-wms.panel>

<div class="mt-6">{{ $fruits->links() }}</div>

{{-- 추가/수정 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="open = false">
    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100 sticky top-0 bg-white">
            <h2 class="font-extrabold text-neutral-900" x-text="mode === 'create' ? '보관 항목 추가' : '보관 항목 수정'"></h2>
            <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" :action="action()" class="p-5 space-y-4">
            @csrf
            <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PATCH"></template>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">제품명 *</label>
                <input type="text" name="name" x-model="form.name" required maxlength="100"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="예: 애플망고">
            </div>
            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">온도(°C)</label>
                    <input type="text" name="temp_c" x-model="form.temp_c" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="예: +10 ~ +14">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">온도(°F)</label>
                    <input type="text" name="temp_f" x-model="form.temp_f" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="예: +50 ~ +57">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">통기공(CMH)</label>
                    <input type="text" name="ventilation" x-model="form.ventilation" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="예: 25 ~ 30">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">상대습도(%)</label>
                    <input type="text" name="humidity" x-model="form.humidity" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="예: 85 ~ 95">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">제습</label>
                    <select name="dehumidification" x-model="form.dehumidification" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                        <option value="끔">끔</option>
                        <option value="켬">켬</option>
                        <option value="끔*">끔*</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">보관기한</label>
                    <input type="text" name="storage_period" x-model="form.storage_period" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="예: 14 ~ 21 / 2 ~ 7개월">
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">비고</label>
                <input type="text" name="note" x-model="form.note" maxlength="255" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4 items-end">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">정렬 순서</label>
                    <input type="number" name="sort_order" x-model.number="form.sort_order" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
                <div class="flex flex-col gap-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_shared" value="1" x-model="form.is_shared" class="rounded text-mango-500 focus:ring-mango-400">
                        <span class="text-sm font-semibold text-neutral-700">매장 공유</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded text-mango-500 focus:ring-mango-400">
                        <span class="text-sm font-semibold text-neutral-700">사용(노출)</span>
                    </label>
                </div>
            </div>
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
    ww.grid('hqFruitStoragesGrid', [
        { header: '제품', name: 'name', width: 150 },
        { header: '온도(°C)', name: 'temp_c', width: 120 },
        { header: '온도(°F)', name: 'temp_f', width: 120 },
        { header: '통기공(CMH)', name: 'ventilation', width: 120 },
        { header: '상대습도(%)', name: 'humidity', width: 110 },
        { header: '제습', name: 'dehumidification', width: 80 },
        { header: '보관기한', name: 'storage_period', width: 150 },
        { header: '매장 공유', name: 'is_shared', width: 90, align: 'center', sortable: false, exportable: false,
          renderer: (v, row) => {
              const form = document.createElement('form'); form.method = 'POST'; form.action = row.toggle_url;
              const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
              const cb = document.createElement('input'); cb.type = 'checkbox'; cb.checked = row.is_shared; cb.title = '매장 공유';
              cb.className = 'rounded w-5 h-5 text-mango-500 focus:ring-mango-400 cursor-pointer';
              cb.addEventListener('change', () => form.submit());
              form.appendChild(cb);
              return form;
          } },
        { header: '관리', name: 'destroy_url', width: 110, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              const wrap = document.createElement('div'); wrap.className = 'flex items-center justify-end gap-2';
              const eb = document.createElement('button'); eb.type = 'button'; eb.textContent = '수정';
              eb.className = 'text-mango-600 hover:text-mango-700 text-xs font-bold';
              eb.addEventListener('click', () => window.dispatchEvent(new CustomEvent('fruit-edit-open', { detail: row.edit })));
              wrap.appendChild(eb);
              const form = document.createElement('form'); form.method = 'POST'; form.action = row.destroy_url;
              form.addEventListener('submit', (e) => { if (!confirm('이 항목을 삭제할까요?')) e.preventDefault(); });
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
