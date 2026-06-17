@extends('portal.layout')
@section('title', '공지사항')

@section('content')
<x-wms.page-head title="공지사항" subtitle="{{ $notice->created_at->format('Y년 m월 d일 H:i') }} · 본사" icon="📢">
    <x-slot:actions>
        <a href="{{ route('portal.notices.index') }}" class="inline-flex items-center gap-1 rounded-xl bg-white border border-neutral-200 hover:bg-neutral-50 text-neutral-600 font-bold px-4 py-2 text-sm transition">← 목록</a>
    </x-slot:actions>
</x-wms.page-head>

<div class="max-w-3xl rounded-2xl bg-white border border-neutral-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-neutral-100">
        <div class="flex items-center gap-2">
            @if ($notice->is_pinned)<span title="상단고정">📌</span>@endif
            <h1 class="text-xl font-extrabold text-neutral-900">{{ $notice->title }}</h1>
        </div>
    </div>
    <div class="px-6 py-6 text-sm text-neutral-700 leading-relaxed whitespace-pre-line">{{ $notice->content }}</div>
</div>
@endsection
