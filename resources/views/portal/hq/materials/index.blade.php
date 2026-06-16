@extends('portal.layout')
@section('title', '재료 관리')

@section('content')
@php
    $hasErr = $errors->any();
    $initForm = $hasErr ? array_merge(['is_active' => (bool) old('is_active'), 'type' => old('type', 'extra')], old())
        : ['is_active' => true, 'type' => 'extra', 'unit' => '개', 'sort_order' => 0];
@endphp
<div x-data="Object.assign(crudModal({{ $hasErr ? 'true' : 'false' }}, {{ \Illuminate\Support\Js::from($initForm) }}), {
        mode: '{{ $hasErr ? old('_mode', 'create') : 'create' }}',
        action: '{{ $hasErr ? old('_action') : '' }}',
        method: '{{ $hasErr && old('_mode') === 'edit' ? 'PUT' : 'POST' }}',
     })">

<x-wms.page-head title="재료 관리" subtitle="추가 품목 재료 · 기타 재료 마스터" icon="🧺" />

@php
    $sections = [
        ['extra', '추가 품목 재료', '📦', $extras],
        ['etc', '기타 재료', '🧩', $etcs],
    ];
@endphp

<div class="space-y-8">
    @foreach ($sections as [$type, $label, $icon, $items])
        <div>
            <x-wms.toolbar :count="$items->count()" :label="$icon . ' ' . $label">
                <button type="button" @click="openCreate('{{ route('portal.hq.materials.store') }}', { type: '{{ $type }}', is_active: true, unit: '개', sort_order: 0 })"
                        class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">+ {{ $label }} 추가</button>
            </x-wms.toolbar>

            <x-wms.panel>
                @if ($items->isEmpty())
                    <p class="px-6 py-12 text-center text-neutral-400">등록된 {{ $label }}가 없습니다.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm whitespace-nowrap">
                            <thead class="bg-neutral-50 text-neutral-500">
                                <tr>
                                    <th class="text-left font-semibold px-5 py-3">코드</th>
                                    <th class="text-left font-semibold px-5 py-3">재료명</th>
                                    <th class="text-left font-semibold px-5 py-3 hidden md:table-cell">분류</th>
                                    <th class="text-left font-semibold px-5 py-3 hidden md:table-cell">규격</th>
                                    <th class="text-left font-semibold px-5 py-3">단위</th>
                                    <th class="text-center font-semibold px-5 py-3 hidden md:table-cell">사용</th>
                                    <th class="text-right font-semibold px-5 py-3 w-28">관리</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-100">
                                @foreach ($items as $m)
                                    <tr class="hover:bg-mango-50/40 transition {{ $m->is_active ? '' : 'opacity-50' }}">
                                        <td class="px-5 py-3.5 font-mono font-bold text-neutral-700">{{ $m->code }}</td>
                                        <td class="px-5 py-3.5 font-bold text-neutral-900">{{ $m->name }}</td>
                                        <td class="px-5 py-3.5 hidden md:table-cell text-neutral-500">{{ $m->category ?: '-' }}</td>
                                        <td class="px-5 py-3.5 hidden md:table-cell text-neutral-500">{{ $m->spec ?: '-' }}</td>
                                        <td class="px-5 py-3.5 text-neutral-500">{{ $m->unit }}</td>
                                        <td class="px-5 py-3.5 text-center hidden md:table-cell">
                                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $m->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-400' }}">{{ $m->is_active ? '사용' : '미사용' }}</span>
                                        </td>
                                        <td class="px-5 py-3.5">
                                            <div class="flex justify-end gap-2">
                                                <button type="button"
                                                        @click="openEdit('{{ route('portal.hq.materials.update', $m) }}', {{ Illuminate\Support\Js::from([
                                                            'type' => $m->type, 'name' => $m->name, 'category' => $m->category, 'unit' => $m->unit,
                                                            'spec' => $m->spec, 'note' => $m->note, 'sort_order' => $m->sort_order, 'is_active' => (bool) $m->is_active,
                                                        ]) }})"
                                                        class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1.5 font-semibold">수정</button>
                                                <form method="POST" action="{{ route('portal.hq.materials.destroy', $m) }}" onsubmit="return confirm('삭제하시겠습니까?')">
                                                    @csrf @method('DELETE')
                                                    <button class="rounded-lg text-rose-600 hover:bg-rose-50 px-3 py-1.5 font-semibold">삭제</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-wms.panel>
        </div>
    @endforeach
</div>

{{-- 재료 신규/수정 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/50" @click="open=false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100 sticky top-0 bg-white rounded-t-2xl">
            <h2 class="text-lg font-extrabold text-neutral-900">
                <span x-text="mode === 'edit' ? '재료 수정' : '재료 추가'"></span>
                <span class="text-sm font-normal text-neutral-400" x-text="'· ' + (form.type === 'etc' ? '기타 재료' : '추가 품목 재료')"></span>
            </h2>
            <button type="button" @click="open=false" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-neutral-100 text-neutral-500">✕</button>
        </div>
        <form :action="action" method="POST" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="_method" :value="method">
            <input type="hidden" name="_mode" :value="mode">
            <input type="hidden" name="_action" :value="action">
            <input type="hidden" name="type" :value="form.type">

            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">재료 구분</label>
                <div class="flex gap-3">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" value="extra" x-model="form.type" class="peer sr-only">
                        <div class="rounded-xl border-2 border-neutral-200 peer-checked:border-mango-500 peer-checked:bg-mango-50 px-4 py-2.5 text-center font-bold transition text-sm">추가 품목 재료</div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" value="etc" x-model="form.type" class="peer sr-only">
                        <div class="rounded-xl border-2 border-neutral-200 peer-checked:border-mango-500 peer-checked:bg-mango-50 px-4 py-2.5 text-center font-bold transition text-sm">기타 재료</div>
                    </label>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">재료명</label>
                    <input type="text" name="name" x-model="form.name" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">분류</label>
                    <input type="text" name="category" x-model="form.category" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="예: 부자재">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">규격</label>
                    <input type="text" name="spec" x-model="form.spec" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="예: 250ml">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">단위</label>
                    <input type="text" name="unit" x-model="form.unit" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="개 / 박스">
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">비고</label>
                <textarea name="note" rows="2" x-model="form.note" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400"></textarea>
            </div>
            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">정렬 순서</label>
                    <input type="number" name="sort_order" x-model="form.sort_order" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <label class="flex items-center gap-2 text-sm font-medium text-neutral-700 mt-7">
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded text-mango-500 focus:ring-mango-400"> 사용
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-7 py-3 transition" x-text="mode === 'edit' ? '수정 저장' : '등록'"></button>
                <button type="button" @click="open=false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 font-bold px-7 py-3 transition">취소</button>
            </div>
        </form>
    </div>
</div>

</div>

@include('portal.partials.crud-modal-script')
@endsection
