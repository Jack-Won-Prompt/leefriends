@extends('portal.layout')
@section('title', '공지사항')

@section('content')
<x-wms.page-head title="공지사항" subtitle="본사에서 전달한 공지사항입니다." icon="📢" />

<div class="space-y-3 max-w-3xl">
    @forelse ($notices as $n)
        <a href="{{ route('portal.notices.show', $n) }}"
           class="block rounded-2xl bg-white border border-neutral-100 shadow-sm hover:border-mango-200 hover:shadow transition px-5 py-4">
            <div class="flex items-center gap-2 mb-1">
                @if ($n->is_pinned)<span title="상단고정">📌</span>@endif
                <span class="font-extrabold text-neutral-900">{{ $n->title }}</span>
            </div>
            <p class="text-sm text-neutral-500 line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($n->content), 120) }}</p>
            <p class="text-xs text-neutral-400 mt-2">{{ $n->created_at->format('Y.m.d H:i') }} · 본사</p>
        </a>
    @empty
        <div class="rounded-2xl bg-white border border-neutral-100 px-5 py-16 text-center text-neutral-400">
            <p class="text-4xl mb-2">📭</p>
            <p class="text-sm">등록된 공지사항이 없습니다.</p>
        </div>
    @endforelse
</div>

@if ($notices->hasPages())
    <div class="mt-5 max-w-3xl">{{ $notices->links() }}</div>
@endif
@endsection
