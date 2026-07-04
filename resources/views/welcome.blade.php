@extends('layouts.app')

@section('title', '망고정 · 리프렌즈 프랜차이즈')
@section('desc', '망고정은 리프렌즈가 운영하는 프리미엄 망고빙수 프랜차이즈입니다. 농익은 애플망고와 검증된 레시피로 사계절 디저트 카페를 함께 만듭니다.')

@section('content')
{{-- Hero --}}
<section class="relative overflow-hidden bg-gradient-to-b from-amber-50 to-white">
    <div class="max-w-6xl mx-auto px-6 py-20 md:py-28 text-center">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold px-3.5 py-1.5">🥭 리프렌즈가 운영하는 프랜차이즈</span>
        <h1 class="mt-5 text-5xl md:text-7xl font-black text-neutral-900 tracking-tight">망고정</h1>
        <p class="mt-4 text-lg md:text-2xl font-bold text-amber-600">농익은 애플망고, 사계절 프리미엄 빙수</p>
        <p class="mt-5 max-w-2xl mx-auto text-neutral-500 leading-relaxed">
            망고정은 <b class="text-neutral-700">리프렌즈</b>가 운영하는 프리미엄 망고빙수 프랜차이즈입니다.<br class="hidden md:block">
            엄선한 애플망고와 검증된 레시피로, 어느 계절에나 사랑받는 디저트 카페를 함께 만듭니다.
        </p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('franchise') }}" class="rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-black px-7 py-3.5 text-sm transition shadow-lg shadow-amber-500/25">창업 문의하기</a>
            <a href="{{ route('store') }}" class="rounded-2xl bg-white border border-neutral-200 hover:bg-neutral-50 text-neutral-700 font-bold px-7 py-3.5 text-sm transition">📍 매장 찾기 @if ($storeCount)<span class="text-amber-600">({{ number_format($storeCount) }})</span>@endif</a>
        </div>
    </div>
</section>

{{-- 대표 메뉴 갤러리 --}}
<section class="max-w-6xl mx-auto px-6 py-16 md:py-24">
    <div class="text-center mb-10">
        <span class="text-amber-600 font-bold text-sm tracking-wider">SIGNATURE MENU</span>
        <h2 class="mt-2 text-3xl md:text-5xl font-black text-neutral-900">망고정 대표 메뉴</h2>
        <p class="mt-3 text-neutral-400">농익은 애플망고로 완성한 프리미엄 빙수 라인업</p>
    </div>

    @if ($menus->isEmpty())
        <p class="text-center text-neutral-400 py-16">준비된 메뉴가 없습니다.</p>
    @else
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
            @foreach ($menus as $m)
                <div class="group rounded-3xl bg-white border border-neutral-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition overflow-hidden">
                    <div class="aspect-square bg-amber-50 grid place-items-center overflow-hidden">
                        @if ($m->image)
                            <img src="{{ asset($m->image) }}" alt="{{ $m->name }}" class="w-full h-full object-cover group-hover:scale-105 transition">
                        @else
                            <span class="text-5xl">🥭</span>
                        @endif
                    </div>
                    <div class="p-4">
                        <div class="flex items-center gap-1.5">
                            <h3 class="font-black text-neutral-900">{{ $m->name }}</h3>
                            @if ($m->badge)<span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-rose-100 text-rose-600 uppercase">{{ $m->badge }}</span>@endif
                        </div>
                        @if ($m->name_en)<p class="text-xs text-neutral-400">{{ $m->name_en }}</p>@endif
                        @if ($m->description)<p class="mt-1.5 text-xs text-neutral-500 line-clamp-2 leading-relaxed">{{ $m->description }}</p>@endif
                        @if ($m->price)<p class="mt-2 font-black text-amber-600">{{ number_format($m->price) }}<span class="text-xs font-bold text-neutral-400">원</span></p>@endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>

{{-- 네이버 블로그 --}}
@if ($blogPosts->isNotEmpty())
<section class="bg-amber-50/60">
    <div class="max-w-6xl mx-auto px-6 py-16 md:py-20">
        <div class="text-center mb-10">
            <span class="text-amber-600 font-bold text-sm tracking-wider">NAVER BLOG</span>
            <h2 class="mt-2 text-3xl md:text-4xl font-black text-neutral-900">망고정 블로그</h2>
            <p class="mt-3 text-neutral-400">망고정의 새로운 소식과 이야기를 만나보세요</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($blogPosts as $p)
                <a href="{{ $p->url }}" target="_blank" rel="noopener"
                   class="group rounded-3xl bg-white border border-neutral-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition overflow-hidden flex flex-col">
                    <div class="aspect-[16/10] bg-amber-50 overflow-hidden">
                        @if ($p->thumbnail_url)
                            <img src="{{ $p->thumbnail_url }}" alt="{{ $p->title }}" referrerpolicy="no-referrer" class="w-full h-full object-cover group-hover:scale-105 transition">
                        @else
                            <div class="w-full h-full grid place-items-center text-4xl">📝</div>
                        @endif
                    </div>
                    <div class="p-5 flex-1 flex flex-col">
                        <h3 class="font-black text-neutral-900 line-clamp-2 group-hover:text-amber-600 transition">{{ $p->title }}</h3>
                        @if ($p->summary)<p class="mt-2 text-sm text-neutral-500 line-clamp-2 leading-relaxed">{{ $p->summary }}</p>@endif
                        <p class="mt-auto pt-3 text-xs text-neutral-400">{{ $p->posted_at?->format('Y.m.d') }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- 네이버 클립 --}}
@if ($clips->isNotEmpty())
<section class="max-w-6xl mx-auto px-6 py-16 md:py-20">
    <div class="text-center mb-10">
        <span class="text-amber-600 font-bold text-sm tracking-wider">NAVER CLIP</span>
        <h2 class="mt-2 text-3xl md:text-4xl font-black text-neutral-900">망고정 클립</h2>
        <p class="mt-3 text-neutral-400">영상으로 즐기는 망고정</p>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-5">
        @foreach ($clips as $c)
            <a href="{{ $c->url }}" target="_blank" rel="noopener"
               class="group relative rounded-3xl overflow-hidden bg-neutral-900 shadow-sm hover:shadow-xl transition aspect-[9/12]">
                @if ($c->thumbnail)
                    <img src="{{ $c->thumbnail_url }}" alt="{{ $c->title }}" referrerpolicy="no-referrer" class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:opacity-100 group-hover:scale-105 transition">
                @endif
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                <div class="absolute inset-0 grid place-items-center">
                    <span class="w-14 h-14 rounded-full bg-white/25 backdrop-blur grid place-items-center text-white text-2xl group-hover:bg-amber-500 transition">▶</span>
                </div>
                <p class="absolute bottom-0 inset-x-0 p-4 text-white font-bold text-sm line-clamp-2">{{ $c->title }}</p>
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- 프랜차이즈 CTA --}}
<section class="bg-neutral-900 text-white">
    <div class="max-w-4xl mx-auto px-6 py-20 text-center">
        <h2 class="text-3xl md:text-4xl font-black">망고정과 함께 창업하세요</h2>
        <p class="mt-4 text-white/60 leading-relaxed">
            리프렌즈의 검증된 시스템과 브랜드로, 사계절 안정적인 디저트 카페를 시작할 수 있습니다.<br class="hidden md:block">
            지금 창업 상담을 신청해 보세요.
        </p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('franchise') }}" class="rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-black px-8 py-4 text-sm transition">창업 문의하기</a>
            <a href="{{ route('menu') }}" class="rounded-2xl bg-white/10 hover:bg-white/15 text-white font-bold px-8 py-4 text-sm transition">전체 메뉴 보기</a>
        </div>
    </div>
</section>
@endsection
