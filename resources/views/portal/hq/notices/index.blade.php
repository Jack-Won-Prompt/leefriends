@extends('portal.layout')
@section('title', '공지사항')

@php
    $audBadge = ['all' => 'bg-mango-100 text-mango-700', 'store' => 'bg-emerald-100 text-emerald-700', 'supplier' => 'bg-sky-100 text-sky-700'];
@endphp

@section('content')
<div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">

<x-wms.page-head title="공지사항" subtitle="매장·공급처에 공지를 작성해 발송합니다. 발송 즉시 실시간 알림이 전달됩니다." icon="📢">
    <x-slot:actions>
        <button type="button" @click="open = true"
                class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">✏️ 공지 작성</button>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.toolbar :count="$notices->total()" />

<x-wms.panel>
    @if ($notices->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">발송한 공지가 없습니다. «공지 작성»으로 첫 공지를 보내보세요.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">제목</th>
                        <th class="text-left font-semibold px-6 py-3">대상</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">작성자</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">발송일</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($notices as $n)
                        <tr class="hover:bg-mango-50/40 transition align-top">
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-1.5">
                                    @if ($n->is_pinned)<span class="text-mango-500" title="상단고정">📌</span>@endif
                                    <span class="font-bold text-neutral-900">{{ $n->title }}</span>
                                </div>
                                <p class="text-xs text-neutral-400 mt-0.5 line-clamp-1 max-w-md">{{ \Illuminate\Support\Str::limit(strip_tags($n->content), 80) }}</p>
                            </td>
                            <td class="px-6 py-3.5"><span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $audBadge[$n->audience] ?? '' }}">{{ $n->audience_label }}</span></td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-500">{{ optional($n->author)->name ?? '본사' }}</td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $n->created_at->format('Y.m.d H:i') }}</td>
                            <td class="px-6 py-3.5 text-right">
                                <form method="POST" action="{{ route('portal.hq.notices.destroy', $n) }}" onsubmit="return confirm('이 공지를 삭제할까요?')">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-500 hover:text-rose-600 text-xs font-bold">삭제</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-wms.panel>

@if ($notices->hasPages())
    <div class="mt-5">{{ $notices->links() }}</div>
@endif

{{-- 작성 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="open = false">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900">✏️ 공지 작성</h2>
            <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" action="{{ route('portal.hq.notices.store') }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">대상 *</label>
                <div class="flex gap-2">
                    @foreach (['all' => '전체', 'store' => '매장', 'supplier' => '공급처'] as $key => $label)
                        <label class="flex-1">
                            <input type="radio" name="audience" value="{{ $key }}" @checked(old('audience', 'all') === $key) class="peer sr-only">
                            <span class="block text-center rounded-xl border border-neutral-200 px-3 py-2 text-sm font-bold text-neutral-600 cursor-pointer peer-checked:border-mango-400 peer-checked:bg-mango-50 peer-checked:text-mango-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">제목 *</label>
                <input type="text" name="title" value="{{ old('title') }}" maxlength="150" required
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="공지 제목">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">내용 *</label>
                <textarea name="content" rows="6" maxlength="5000" required
                          class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="공지 내용을 입력하세요">{{ old('content') }}</textarea>
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned')) class="rounded text-mango-500 focus:ring-mango-400">
                <span class="text-sm font-semibold text-neutral-700">상단 고정 📌</span>
            </label>
            @error('title')<p class="text-xs text-rose-500">{{ $message }}</p>@enderror
            @error('content')<p class="text-xs text-rose-500">{{ $message }}</p>@enderror
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition">발송하기</button>
                <button type="button" @click="open = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm transition">취소</button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
