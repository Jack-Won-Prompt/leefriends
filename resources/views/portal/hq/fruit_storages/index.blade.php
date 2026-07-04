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
     }">

<x-wms.page-head title="과일 보관 관리" subtitle="과일·채소의 냉장/냉동 보관 가이드(ZIM 권장)입니다. ‘매장 공유’를 체크하면 매장 포털에서 열람할 수 있습니다." icon="🧊">
    <x-slot:actions>
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ $q }}" placeholder="제품 검색" class="rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm py-2">
            <button class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-3.5 py-2 text-sm">검색</button>
        </form>
        <button type="button" @click="openCreate()" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 항목 추가</button>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.panel>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-4 py-3">제품</th>
                <th class="text-left font-semibold px-4 py-3">온도(°C)</th>
                <th class="text-left font-semibold px-4 py-3 hidden lg:table-cell">온도(°F)</th>
                <th class="text-left font-semibold px-4 py-3 hidden lg:table-cell">통기공(CMH)</th>
                <th class="text-left font-semibold px-4 py-3">상대습도(%)</th>
                <th class="text-left font-semibold px-4 py-3 hidden md:table-cell">제습</th>
                <th class="text-left font-semibold px-4 py-3">보관기한</th>
                <th class="text-center font-semibold px-4 py-3">매장 공유</th>
                <th class="text-right font-semibold px-4 py-3">관리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($fruits as $f)
                <tr class="hover:bg-mango-50/40">
                    <td class="px-4 py-3 font-bold text-neutral-900 whitespace-nowrap">{{ $f->name }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $f->temp_c }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell whitespace-nowrap text-neutral-500">{{ $f->temp_f }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell whitespace-nowrap text-neutral-500">{{ $f->ventilation }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $f->humidity }}</td>
                    <td class="px-4 py-3 hidden md:table-cell whitespace-nowrap text-neutral-500">{{ $f->dehumidification }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $f->storage_period }}</td>
                    <td class="px-4 py-3 text-center">
                        <form method="POST" action="{{ route('portal.hq.fruit_storages.toggle_share', $f) }}" class="inline">
                            @csrf
                            <input type="checkbox" onchange="this.form.submit()" {{ $f->is_shared ? 'checked' : '' }}
                                   class="rounded w-5 h-5 text-mango-500 focus:ring-mango-400 cursor-pointer" title="매장 공유">
                        </form>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <button type="button" @click="openEdit({{ Illuminate\Support\Js::from([
                                    'id' => $f->id, 'name' => $f->name, 'temp_c' => $f->temp_c, 'temp_f' => $f->temp_f,
                                    'ventilation' => $f->ventilation, 'humidity' => $f->humidity, 'dehumidification' => $f->dehumidification,
                                    'storage_period' => $f->storage_period, 'note' => $f->note, 'sort_order' => (int) $f->sort_order,
                                    'is_shared' => (bool) $f->is_shared, 'is_active' => (bool) $f->is_active,
                                ]) }})" class="text-mango-600 hover:text-mango-700 text-xs font-bold mr-2">수정</button>
                        <form method="POST" action="{{ route('portal.hq.fruit_storages.destroy', $f) }}" class="inline" onsubmit="return confirm('이 항목을 삭제할까요?')">
                            @csrf @method('DELETE')
                            <button class="text-rose-500 hover:text-rose-600 text-xs font-bold">삭제</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="px-4 py-12 text-center text-neutral-400">등록된 보관 항목이 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
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
@endsection
