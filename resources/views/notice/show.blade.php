@extends('layouts.app')

@section('title', $notice->title . ' · LEEFRIENDS')

@section('content')

<section class="pt-[72px]">
    <div class="bg-mango-50 py-14">
        <div class="max-w-3xl mx-auto px-5 lg:px-8">
            <span class="inline-block text-xs font-bold px-3 py-1 rounded-full
                {{ $notice->category === 'event' ? 'bg-rose-100 text-rose-600' : ($notice->category === 'news' ? 'bg-sky-100 text-sky-600' : 'bg-mango-100 text-mango-700') }}">
                {{ $notice->category_label }}
            </span>
            <h1 class="text-2xl md:text-4xl font-black text-neutral-900 mt-4 leading-snug text-balance">{{ $notice->title }}</h1>
            <p class="text-sm text-neutral-500 mt-4">
                {{ $notice->published_at?->format('Y년 m월 d일') }} · 조회 {{ number_format($notice->views) }}
            </p>
        </div>
    </div>
</section>

<section class="py-16">
    <div class="max-w-3xl mx-auto px-5 lg:px-8">
        <div class="prose prose-neutral max-w-none text-neutral-700 leading-relaxed whitespace-pre-line text-[17px]">{{ $notice->content }}</div>

        {{-- prev / next --}}
        <div class="mt-16 border-t border-neutral-200 divide-y divide-neutral-100">
            @if ($next)
                <a href="{{ route('notice.show', $next) }}" class="flex items-center gap-4 py-4 group">
                    <span class="text-sm font-bold text-neutral-400 w-12">이전</span>
                    <span class="flex-1 font-medium text-neutral-700 group-hover:text-mango-600 truncate transition">{{ $next->title }}</span>
                </a>
            @endif
            @if ($prev)
                <a href="{{ route('notice.show', $prev) }}" class="flex items-center gap-4 py-4 group">
                    <span class="text-sm font-bold text-neutral-400 w-12">다음</span>
                    <span class="flex-1 font-medium text-neutral-700 group-hover:text-mango-600 truncate transition">{{ $prev->title }}</span>
                </a>
            @endif
        </div>

        <div class="text-center mt-12">
            <a href="{{ route('notice.index') }}" class="inline-flex rounded-full bg-neutral-900 hover:bg-mango-600 text-white font-bold px-8 py-3.5 transition">목록으로</a>
        </div>
    </div>
</section>

@endsection
