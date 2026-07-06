@extends('layouts.app')

@section('title', 'LEEFRIENDS · 프리미엄 망고빙수 전문점')

@section('content')

{{-- ===================== HERO SLIDER ===================== --}}
<section class="relative h-screen min-h-[640px] overflow-hidden"
         x-data="{ active: 0, slides: [
            { img: '{{ asset('images/hero/slide1_mango_farm.jpg') }}', fb: '{{ asset('images/hero/slide1.svg') }}', kicker: 'PREMIUM MANGO DESSERT', title: '농익은 애플망고,<br>그대로 담다', sub: '한 입에 퍼지는 진짜 망고의 계절' },
            { img: '{{ asset('images/hero/slide2_mango_bingsu.jpg') }}', fb: '{{ asset('images/hero/slide2.svg') }}', kicker: 'SIGNATURE BINGSU', title: '망고치즈빙수', sub: '부드러운 우유빙수 위 가득한 생망고' },
            { img: '{{ asset('images/hero/slide3_cafe_interior.jpg') }}', fb: '{{ asset('images/hero/slide3.svg') }}', kicker: 'FRANCHISE', title: '망고정<br>창업', sub: '사계절 디저트 카페, 함께 시작하세요' },
         ] }"
         x-init="setInterval(() => active = (active + 1) % slides.length, 5000)">

    <template x-for="(s, i) in slides" :key="i">
        <div class="absolute inset-0 transition-opacity duration-1000"
             :class="active === i ? 'opacity-100' : 'opacity-0 pointer-events-none'">
            <img :src="s.img" :data-fb="s.fb" onerror="this.onerror=null;this.src=this.dataset.fb" class="absolute inset-0 w-full h-full object-cover" alt="">
            <div class="absolute inset-0 bg-gradient-to-r from-black/45 via-black/20 to-transparent"></div>
            <div class="relative z-10 h-full max-w-7xl mx-auto px-5 lg:px-8 flex flex-col justify-center">
                <p class="text-white/90 font-bold tracking-[0.25em] text-sm mb-5" x-text="s.kicker"
                   :class="active === i ? 'animate-fadeup' : ''"></p>
                <h1 class="text-white font-black leading-[1.08] text-5xl md:text-7xl mb-6 text-balance"
                    x-html="s.title" :class="active === i ? 'animate-fadeup' : ''"></h1>
                <p class="text-white/90 text-lg md:text-2xl font-medium" x-text="s.sub"
                   :class="active === i ? 'animate-fadeup' : ''"></p>
                <div class="mt-10 flex flex-wrap gap-3" :class="active === i ? 'animate-fadeup' : ''">
                    <a href="{{ route('menu') }}" class="rounded-full bg-white text-mango-700 font-bold px-7 py-3.5 shadow-lg hover:scale-105 active:scale-95 transition">메뉴 보기</a>
                    <a href="{{ route('franchise') }}#inquiry" class="rounded-full bg-mango-500 text-white font-bold px-7 py-3.5 shadow-soft hover:scale-105 active:scale-95 transition">창업 문의 →</a>
                </div>
            </div>
        </div>
    </template>

    {{-- dots --}}
    <div class="absolute bottom-10 left-1/2 -translate-x-1/2 z-20 flex gap-2.5">
        <template x-for="(s, i) in slides" :key="'d'+i">
            <button @click="active = i" class="h-2.5 rounded-full transition-all duration-300"
                    :class="active === i ? 'w-8 bg-white' : 'w-2.5 bg-white/50 hover:bg-white/80'"></button>
        </template>
    </div>

    <div class="absolute bottom-10 right-8 z-20 hidden md:flex items-center gap-2 text-white/80 text-xs tracking-widest animate-floaty">
        <span>SCROLL</span><span class="block w-px h-8 bg-white/60"></span>
    </div>
</section>

{{-- ===================== STATS BAR ===================== --}}
<section class="bg-mango-500 text-white">
    <div class="max-w-7xl mx-auto px-5 lg:px-8 py-8 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
        @foreach ([['100%','애플망고 원물'],[$storeCount.'+','전국 가맹점'],['4.9','고객 만족도'],['사계절','꾸준한 매출']] as [$num, $label])
            <div>
                <p class="text-3xl md:text-4xl font-black">{{ $num }}</p>
                <p class="text-white/85 text-sm mt-1 font-medium">{{ $label }}</p>
            </div>
        @endforeach
    </div>
</section>

{{-- ===================== BRAND INTRO ===================== --}}
<section class="py-24 lg:py-32">
    <div class="max-w-7xl mx-auto px-5 lg:px-8 grid lg:grid-cols-2 gap-14 items-center">
        <div class="reveal">
            <p class="text-mango-600 font-bold tracking-widest text-sm mb-4">BRAND STORY</p>
            <h2 class="text-3xl md:text-5xl font-black leading-tight text-neutral-900 mb-6 text-balance">
                진심을 담은<br>한 그릇의 행복
            </h2>
            <p class="text-neutral-500 text-lg leading-relaxed mb-8">
                LEEFRIENDS는 정성껏 키운 농익은 애플망고만을 고집합니다.<br class="hidden md:block">
                인공 향료 없이, 자연 그대로의 달콤함을 빙수 한 그릇에 정성껏 담았습니다.
            </p>
            <div class="grid sm:grid-cols-3 gap-4">
                @foreach ([['🥭','엄선한 원물','농익은 애플망고 100%'],['❄️','눈처럼 고운 빙질','매일 직접 갈아낸 우유얼음'],['🌿','정직한 레시피','인공첨가물 NO']] as [$ico,$t,$d])
                    <div class="rounded-2xl bg-mango-50 p-5">
                        <div class="text-3xl mb-2">{{ $ico }}</div>
                        <p class="font-bold text-neutral-900">{{ $t }}</p>
                        <p class="text-sm text-neutral-500 mt-1">{{ $d }}</p>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('brand') }}" class="inline-flex items-center gap-2 mt-8 font-bold text-mango-700 hover:gap-3 transition-all">
                브랜드 더 알아보기 <span>→</span>
            </a>
        </div>
        <div class="reveal relative">
            <div class="aspect-[4/5] rounded-[2.5rem] overflow-hidden shadow-soft">
                <img src="{{ asset('images/brand/brand_story_mango.jpg') }}" onerror="this.onerror=null;this.src='{{ asset('images/brand/story.svg') }}'" alt="브랜드 스토리" class="w-full h-full object-cover">
            </div>
            <div class="absolute -bottom-6 -left-6 bg-white rounded-2xl shadow-card p-5 w-44 animate-floaty">
                <p class="text-4xl font-black text-mango-500">SINCE</p>
                <p class="text-2xl font-black text-neutral-900">2026</p>
            </div>
        </div>
    </div>
</section>

{{-- ===================== SIGNATURE MENU ===================== --}}
<section class="py-24 bg-gradient-to-b from-mango-50 to-white">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-14 reveal">
            <p class="text-mango-600 font-bold tracking-widest text-sm mb-3">SIGNATURE MENU</p>
            <h2 class="text-3xl md:text-5xl font-black text-neutral-900">리프렌즈 시그니처</h2>
            <p class="text-neutral-500 mt-4 text-lg">리프렌즈를 대표하는 가장 사랑받는 메뉴</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-{{ max(2, min(4, $signatures->count())) }} gap-6 reveal">
            @foreach ($signatures as $menu)
                @include('partials.menu-card', ['menu' => $menu])
            @endforeach
        </div>
    </div>
</section>

{{-- ===================== BEST / POPULAR ===================== --}}
<section class="py-24">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="flex items-end justify-between mb-12 reveal">
            <div>
                <p class="text-mango-600 font-bold tracking-widest text-sm mb-3">BEST ITEMS</p>
                <h2 class="text-3xl md:text-5xl font-black text-neutral-900">베스트 인기 메뉴</h2>
            </div>
            <a href="{{ route('menu') }}" class="hidden md:inline-flex items-center gap-2 font-bold text-neutral-700 hover:text-mango-600 transition">
                전체 메뉴 <span>→</span>
            </a>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 reveal">
            @foreach ($populars as $menu)
                @include('partials.menu-card', ['menu' => $menu])
            @endforeach
        </div>
        <div class="text-center mt-10 md:hidden">
            <a href="{{ route('menu') }}" class="inline-flex rounded-full border-2 border-mango-500 text-mango-700 font-bold px-7 py-3">전체 메뉴 보기</a>
        </div>
    </div>
</section>

{{-- ===================== NAVER BLOG ===================== --}}
@if ($blogPosts->isNotEmpty())
<section class="py-24 bg-gradient-to-b from-white to-mango-50/60">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="flex items-end justify-between mb-12 reveal">
            <div>
                <p class="text-mango-600 font-bold tracking-widest text-sm mb-3">NAVER BLOG</p>
                <h2 class="text-3xl md:text-5xl font-black text-neutral-900">망고정 블로그</h2>
            </div>
            <a href="https://blog.naver.com/{{ config('services.naver.blog_id') }}" target="_blank" rel="noopener"
               class="hidden md:inline-flex items-center gap-2 font-bold text-neutral-700 hover:text-mango-600 transition">
                블로그 바로가기 <span>→</span>
            </a>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 reveal">
            @foreach ($blogPosts as $p)
                <a href="{{ $p->url }}" target="_blank" rel="noopener"
                   class="group rounded-3xl bg-white border border-neutral-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition overflow-hidden flex flex-col">
                    <div class="aspect-[16/10] bg-mango-50 overflow-hidden">
                        @if ($p->thumbnail_url)
                            <img src="{{ $p->thumbnail_url }}" alt="{{ $p->title }}" referrerpolicy="no-referrer" class="w-full h-full object-cover group-hover:scale-105 transition">
                        @else
                            <div class="w-full h-full grid place-items-center text-4xl">📝</div>
                        @endif
                    </div>
                    <div class="p-6 flex-1 flex flex-col">
                        <h3 class="font-black text-neutral-900 line-clamp-2 group-hover:text-mango-600 transition">{{ $p->title }}</h3>
                        @if ($p->summary)<p class="mt-2 text-sm text-neutral-500 line-clamp-2 leading-relaxed">{{ $p->summary }}</p>@endif
                        <p class="mt-auto pt-4 text-xs text-neutral-400">{{ $p->posted_at?->format('Y.m.d') }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ===================== NAVER CLIP ===================== --}}
@if ($clips->isNotEmpty())
<section class="py-24">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-12 reveal">
            <p class="text-mango-600 font-bold tracking-widest text-sm mb-3">NAVER CLIP</p>
            <h2 class="text-3xl md:text-5xl font-black text-neutral-900">망고정 클립</h2>
            <p class="mt-3 text-neutral-400">영상으로 즐기는 망고정</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-5 reveal">
            @foreach ($clips as $c)
                <a href="{{ $c->url }}" target="_blank" rel="noopener"
                   class="group relative rounded-3xl overflow-hidden bg-neutral-900 shadow-sm hover:shadow-xl transition aspect-[9/12]">
                    @if ($c->thumbnail)
                        <img src="{{ $c->thumbnail_url }}" alt="{{ $c->title }}" referrerpolicy="no-referrer" class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:opacity-100 group-hover:scale-105 transition">
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>
                    <div class="absolute inset-0 grid place-items-center">
                        <span class="w-12 h-12 rounded-full bg-white/25 backdrop-blur grid place-items-center text-white text-xl group-hover:bg-mango-500 transition">▶</span>
                    </div>
                    <p class="absolute bottom-0 inset-x-0 p-3 text-white font-bold text-xs line-clamp-2">{{ $c->title }}</p>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ===================== FRANCHISE HIGHLIGHT ===================== --}}
<section class="relative py-28 overflow-hidden bg-neutral-900">
    <img src="{{ asset('images/hero/franchise_cafe_v3.jpg') }}" onerror="this.onerror=null;this.src='{{ asset('images/hero/slide3.svg') }}'" class="absolute inset-0 w-full h-full object-cover opacity-40" alt="">
    <div class="absolute inset-0 bg-gradient-to-t from-neutral-900 via-neutral-900/70 to-neutral-900/40"></div>
    <div class="relative z-10 max-w-6xl mx-auto px-5 lg:px-8 text-white">
        <div class="text-center reveal">
            <p class="text-mango-300 font-bold tracking-widest text-sm mb-5">FRANCHISE</p>
            <h2 class="text-3xl md:text-5xl font-black leading-tight mb-5 text-balance">
                사계절 잘 팔리는 디저트 카페<br>망고정과 시작하세요
            </h2>
            <p class="text-white/80 text-lg">낮은 창업비 · 높은 수익률 · 본사 토탈 지원</p>
        </div>

        {{-- 핵심 수치 (카운트업) --}}
        <div class="mt-12 grid grid-cols-1 sm:grid-cols-3 gap-5 max-w-4xl mx-auto stagger">
            @foreach ([
                ['월 평균 매출','3000','만원+','매장 평균 월매출'],
                ['영업 마진','28','%','평균 영업 마진율'],
                ['창업 비용','4150','만원~','점포·상권에 따라 상이'],
            ] as [$label,$num,$suffix,$desc])
                <div class="reveal rv-scale rounded-3xl bg-white/5 border border-white/10 px-4 py-8 text-center">
                    <p class="text-sm font-bold text-white/60">{{ $label }}</p>
                    <p class="mt-2 text-3xl md:text-4xl font-black text-mango-300 whitespace-nowrap"><span data-countup="{{ $num }}" data-suffix="{{ $suffix }}">0{{ $suffix }}</span></p>
                    <p class="mt-2 text-xs text-white/50">{{ $desc }}</p>
                </div>
            @endforeach
        </div>

        {{-- 강점 요약 --}}
        <div class="mt-8 grid grid-cols-2 lg:grid-cols-4 gap-3 max-w-4xl mx-auto stagger">
            @foreach ([
                ['🍧','차별화 경쟁력'],['📈','사계절 안정 매출'],['🤝','본사 토탈 지원'],['💡','간편한 운영'],
            ] as [$ico,$t])
                <div class="reveal rounded-2xl bg-white/5 border border-white/10 px-4 py-4 flex items-center gap-2.5">
                    <span class="text-xl">{{ $ico }}</span>
                    <span class="text-sm font-bold text-white/85">{{ $t }}</span>
                </div>
            @endforeach
        </div>

        {{-- CTA --}}
        <div class="mt-12 flex flex-wrap justify-center gap-3 reveal">
            <a href="{{ route('franchise') }}#inquiry" class="rounded-full bg-mango-500 hover:bg-mango-600 px-8 py-4 font-bold shadow-soft hover:scale-105 transition">온라인 창업문의</a>
            <a href="{{ route('franchise') }}" class="rounded-full bg-white text-neutral-900 hover:bg-neutral-100 px-8 py-4 font-black shadow-soft hover:scale-105 transition">창업 안내 자세히 보기 →</a>
        </div>
    </div>
</section>

{{-- ===================== NOTICE + STORE ===================== --}}
<section class="py-24">
    <div class="max-w-7xl mx-auto px-5 lg:px-8 grid lg:grid-cols-2 gap-12">
        {{-- notices --}}
        <div class="reveal">
            <div class="flex items-center justify-between mb-7">
                <h2 class="text-2xl font-black text-neutral-900">공지 · 소식</h2>
                <a href="{{ route('notice.index') }}" class="text-sm font-bold text-neutral-500 hover:text-mango-600 transition">전체보기 +</a>
            </div>
            <ul class="divide-y divide-neutral-100">
                @foreach ($notices as $n)
                    <li>
                        <a href="{{ route('notice.show', $n) }}" class="flex items-center gap-4 py-4 group">
                            <span class="shrink-0 text-xs font-bold px-2.5 py-1 rounded-full bg-mango-100 text-mango-700">{{ $n->category_label }}</span>
                            <span class="flex-1 font-semibold text-neutral-700 group-hover:text-mango-600 truncate transition">{{ $n->title }}</span>
                            <span class="shrink-0 text-sm text-neutral-400">{{ $n->published_at?->format('Y.m.d') }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        {{-- store cta --}}
        <div class="reveal rounded-3xl bg-mango-50 p-9 flex flex-col justify-center">
            <p class="text-mango-600 font-bold tracking-widest text-sm mb-3">STORE</p>
            <h2 class="text-2xl md:text-3xl font-black text-neutral-900 mb-3">가까운 리프렌즈 매장을 찾아보세요</h2>
            <p class="text-neutral-500 mb-6">전국 {{ $storeCount }}개 매장에서 신선한 망고빙수를 만나보실 수 있습니다.</p>
            <a href="{{ route('store') }}" class="self-start inline-flex items-center gap-2 rounded-full bg-mango-500 hover:bg-mango-600 text-white font-bold px-7 py-3.5 shadow-soft transition">
                매장 찾기 <span>→</span>
            </a>
        </div>
    </div>
</section>

@endsection
