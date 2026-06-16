@extends('admin.layout')
@section('title', '메뉴 관리')

@section('content')
@php
    $images = collect(glob(public_path('images/menu/*.svg')))->map(fn ($p) => 'images/menu/' . basename($p))->values();
    $hasErr = $errors->any();
    $initForm = $hasErr ? array_merge(['is_active' => (bool) old('is_active')], old())
        : ['is_active' => true, 'category' => 'bingsu', 'badge' => '', 'image' => $images->first(), 'sort_order' => 0, 'price' => 0];
    $assetBase = asset('') ;
@endphp
<div x-data="Object.assign(crudModal({{ $hasErr ? 'true' : 'false' }}, {{ \Illuminate\Support\Js::from($initForm) }}), {
        mode: '{{ $hasErr ? old('_mode', 'create') : 'create' }}',
        action: '{{ $hasErr ? old('_action') : '' }}',
        method: '{{ $hasErr && old('_mode') === 'edit' ? 'PUT' : 'POST' }}',
        assetBase: '{{ $assetBase }}',
     })">

<div class="flex justify-end mb-5">
    <button type="button" @click="openCreate('{{ route('admin.menus.store') }}', { is_active: true, category: 'bingsu', badge: '', image: '{{ $images->first() }}', sort_order: 0, price: 0 })"
            class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2.5 transition">+ 새 메뉴 추가</button>
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    @if ($menus->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">등록된 메뉴가 없습니다.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3 w-20">이미지</th>
                    <th class="text-left font-semibold px-6 py-3">메뉴명</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell w-24">분류</th>
                    <th class="text-left font-semibold px-6 py-3 w-28">가격</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell w-20">노출</th>
                    <th class="text-right font-semibold px-6 py-3 w-32">관리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($menus as $m)
                    <tr class="hover:bg-mango-50/40 transition">
                        <td class="px-6 py-3"><img src="{{ asset($m->image) }}" class="w-12 h-12 rounded-lg object-cover" alt=""></td>
                        <td class="px-6 py-3 font-bold text-neutral-900">{{ $m->name }}
                            @if ($m->badge)<span class="ml-1 text-[10px] font-bold text-mango-600">[{{ strtoupper($m->badge) }}]</span>@endif
                        </td>
                        <td class="px-6 py-3 hidden md:table-cell text-neutral-500">{{ $m->category_label }}</td>
                        <td class="px-6 py-3 font-bold text-mango-700">{{ number_format($m->price) }}원</td>
                        <td class="px-6 py-3 hidden md:table-cell">
                            <span class="text-xs font-bold px-2 py-1 rounded-full {{ $m->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-400' }}">{{ $m->is_active ? '노출' : '숨김' }}</span>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        @click="openEdit('{{ route('admin.menus.update', $m) }}', {{ Illuminate\Support\Js::from([
                                            'category' => $m->category, 'badge' => $m->badge ?? '', 'name' => $m->name, 'name_en' => $m->name_en,
                                            'price' => $m->price, 'sort_order' => $m->sort_order, 'description' => $m->description,
                                            'image' => $m->image, 'is_active' => (bool) $m->is_active,
                                        ]) }})"
                                        class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1.5 font-semibold">수정</button>
                                <form method="POST" action="{{ route('admin.menus.destroy', $m) }}" onsubmit="return confirm('삭제하시겠습니까?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg text-rose-600 hover:bg-rose-50 px-3 py-1.5 font-semibold">삭제</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="mt-6">{{ $menus->links() }}</div>

{{-- 메뉴 신규/수정 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/50" @click="open=false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100 sticky top-0 bg-white rounded-t-2xl">
            <h2 class="text-lg font-extrabold text-neutral-900" x-text="mode === 'edit' ? '메뉴 수정' : '새 메뉴 추가'"></h2>
            <button type="button" @click="open=false" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-neutral-100 text-neutral-500">✕</button>
        </div>
        <form :action="action" method="POST" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="_method" :value="method">
            <input type="hidden" name="_mode" :value="mode">
            <input type="hidden" name="_action" :value="action">

            <div class="flex gap-5 items-start">
                <img :src="assetBase + form.image" class="w-24 h-24 rounded-2xl object-cover bg-mango-50 shrink-0" alt="">
                <div class="flex-1">
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">대표 이미지</label>
                    <select name="image" x-model="form.image" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                        @foreach ($images as $img)
                            <option value="{{ $img }}">{{ basename($img) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">분류</label>
                    <select name="category" x-model="form.category" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                        @foreach (\App\Models\Menu::CATEGORIES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">뱃지</label>
                    <select name="badge" x-model="form.badge" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                        <option value="">없음</option>
                        @foreach (['best' => 'BEST', 'new' => 'NEW', 'hot' => 'HOT'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">메뉴명</label>
                    <input type="text" name="name" x-model="form.name" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">영문명</label>
                    <input type="text" name="name_en" x-model="form.name_en" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">가격(원)</label>
                    <input type="number" name="price" x-model="form.price" required min="0" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">정렬 순서</label>
                    <input type="number" name="sort_order" x-model="form.sort_order" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">설명</label>
                <textarea name="description" rows="3" x-model="form.description" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400"></textarea>
            </div>

            <label class="flex items-center gap-2 text-sm font-medium text-neutral-700">
                <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded text-mango-500 focus:ring-mango-400"> 홈페이지에 노출
            </label>

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
