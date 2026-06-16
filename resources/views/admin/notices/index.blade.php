@extends('admin.layout')
@section('title', '공지사항 관리')

@section('content')
@php
    $hasErr = $errors->any();
    $initForm = $hasErr
        ? array_merge(['is_pinned' => (bool) old('is_pinned')], old())
        : ['is_pinned' => false, 'category' => 'notice', 'published_at' => now()->format('Y-m-d')];
@endphp
<div x-data="Object.assign(crudModal({{ $hasErr ? 'true' : 'false' }}, {{ \Illuminate\Support\Js::from($initForm) }}), {
        mode: '{{ $hasErr ? old('_mode', 'create') : 'create' }}',
        action: '{{ $hasErr ? old('_action') : '' }}',
        method: '{{ $hasErr && old('_mode') === 'edit' ? 'PUT' : 'POST' }}',
     })">

<div class="flex justify-end mb-5">
    <button type="button" @click="openCreate('{{ route('admin.notices.store') }}', { is_pinned: false, category: 'notice', published_at: '{{ now()->format('Y-m-d') }}' })"
            class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2.5 transition">+ 새 공지 작성</button>
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    @if ($notices->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">등록된 공지가 없습니다.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3 w-24">분류</th>
                    <th class="text-left font-semibold px-6 py-3">제목</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell w-24">조회</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell w-32">게시일</th>
                    <th class="text-right font-semibold px-6 py-3 w-32">관리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($notices as $n)
                    <tr class="hover:bg-mango-50/40 transition">
                        <td class="px-6 py-3.5"><span class="text-xs font-bold px-2.5 py-1 rounded-full bg-mango-100 text-mango-700">{{ $n->category_label }}</span></td>
                        <td class="px-6 py-3.5 font-bold text-neutral-900">@if ($n->is_pinned)📌 @endif{{ $n->title }}</td>
                        <td class="px-6 py-3.5 hidden md:table-cell text-neutral-500">{{ number_format($n->views) }}</td>
                        <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $n->published_at?->format('Y.m.d') }}</td>
                        <td class="px-6 py-3.5">
                            <div class="flex justify-end gap-2">
                                <button type="button"
                                        @click="openEdit('{{ route('admin.notices.update', $n) }}', {{ Illuminate\Support\Js::from([
                                            'category' => $n->category, 'title' => $n->title, 'content' => $n->content,
                                            'is_pinned' => (bool) $n->is_pinned,
                                            'published_at' => optional($n->published_at)->format('Y-m-d'),
                                        ]) }})"
                                        class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1.5 font-semibold">수정</button>
                                <form method="POST" action="{{ route('admin.notices.destroy', $n) }}" onsubmit="return confirm('삭제하시겠습니까?')">
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

<div class="mt-6">{{ $notices->links() }}</div>

{{-- 공지 신규/수정 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/50" @click="open=false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100 sticky top-0 bg-white rounded-t-2xl">
            <h2 class="text-lg font-extrabold text-neutral-900" x-text="mode === 'edit' ? '공지 수정' : '새 공지 작성'"></h2>
            <button type="button" @click="open=false" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-neutral-100 text-neutral-500">✕</button>
        </div>
        <form :action="action" method="POST" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="_method" :value="method">
            <input type="hidden" name="_mode" :value="mode">
            <input type="hidden" name="_action" :value="action">

            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">분류</label>
                    <select name="category" x-model="form.category" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                        @foreach (\App\Models\Notice::CATEGORIES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">게시일</label>
                    <input type="date" name="published_at" x-model="form.published_at" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">제목</label>
                <input type="text" name="title" x-model="form.title" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">내용</label>
                <textarea name="content" rows="8" x-model="form.content" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400"></textarea>
            </div>
            <label class="flex items-center gap-2 text-sm font-medium text-neutral-700">
                <input type="checkbox" name="is_pinned" value="1" x-model="form.is_pinned" class="rounded text-mango-500 focus:ring-mango-400"> 상단 고정 (📌)
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
