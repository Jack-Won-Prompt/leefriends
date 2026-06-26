@extends('layouts.app')

@section('title', '브랜드 스토리 · LEEFRIENDS')

@section('content')

@include('partials.page-hero', [
    'eyebrow' => 'BRAND STORY',
    'title' => '리프렌즈 이야기',
    'subtitle' => '농익은 애플망고의 달콤함을 가장 정직하게',
])

{{-- Philosophy --}}
<section class="py-24">
    <div class="max-w-4xl mx-auto px-5 lg:px-8 text-center reveal">
        <p class="text-mango-600 font-bold tracking-widest text-sm mb-5">OUR PHILOSOPHY</p>
        <h2 class="text-3xl md:text-5xl font-black text-neutral-900 leading-tight mb-8 text-balance">
            “좋은 재료가<br>좋은 디저트를 만듭니다”
        </h2>
        <p class="text-lg text-neutral-500 leading-relaxed">
            LEEFRIENDS는 화려한 장식보다 재료의 본질에 집중합니다.<br class="hidden md:block">
            따사로운 햇살을 머금고 자란 농익은 애플망고, 매일 새벽 직접 갈아내는 우유빙,
            그리고 인공 첨가물 없는 정직한 레시피. 단순하지만 타협하지 않는 이 원칙이
            리프렌즈의 가장 큰 경쟁력입니다.
        </p>
    </div>
</section>

{{-- 3 values --}}
<section class="pb-24">
    <div class="max-w-7xl mx-auto px-5 lg:px-8 grid md:grid-cols-3 gap-8">
        @foreach ([
            ['images/brand/quality.svg','01','엄선한 애플망고','당도 높은 제철 애플망고만을 선별해 매장에서 직접 손질합니다.'],
            ['images/brand/story.svg','02','매일 만드는 신선함','우유빙은 매일 새벽 매장에서 직접 갈아내어 눈처럼 고운 빙질을 유지합니다.'],
            ['images/brand/space.svg','03','머무르고 싶은 공간','감각적인 인테리어로 디저트와 함께 일상의 쉼표를 선물합니다.'],
        ] as [$img,$no,$t,$d])
            <div class="reveal rounded-3xl overflow-hidden bg-white shadow-card">
                <div class="aspect-[4/3] overflow-hidden">
                    <img src="{{ asset($img) }}" alt="{{ $t }}" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                </div>
                <div class="p-7">
                    <span class="text-mango-400 font-black text-lg">{{ $no }}</span>
                    <h3 class="text-xl font-extrabold text-neutral-900 mt-1 mb-2">{{ $t }}</h3>
                    <p class="text-neutral-500 leading-relaxed">{{ $d }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- timeline --}}
<section class="py-24 bg-mango-50">
    <div class="max-w-3xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-14 reveal">
            <p class="text-mango-600 font-bold tracking-widest text-sm mb-3">HISTORY</p>
            <h2 class="text-3xl md:text-4xl font-black text-neutral-900">리프렌즈의 발자취</h2>
        </div>
        <div class="space-y-8">
            @foreach ([
                ['2026.01','브랜드 «LEEFRIENDS» 론칭, 강남본점 오픈'],
                ['2026.03','애플망고 농가 직거래 계약 체결'],
                ['2026.05','가맹 사업 본격 시작, 전국 매장 확대'],
                ['2026.06','프리미엄 망고빙수 시즌 메뉴 출시'],
            ] as [$year,$desc])
                <div class="flex gap-6 items-start reveal">
                    <div class="shrink-0 w-20 text-right font-black text-mango-600">{{ $year }}</div>
                    <div class="relative pl-6 border-l-2 border-mango-300 pb-2">
                        <span class="absolute -left-[7px] top-1.5 w-3 h-3 rounded-full bg-mango-500"></span>
                        <p class="text-neutral-700 font-medium">{{ $desc }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="py-20 text-center">
    <div class="max-w-2xl mx-auto px-5">
        <h2 class="text-2xl md:text-3xl font-black text-neutral-900 mb-6">지금, 리프렌즈의 맛을 경험해 보세요</h2>
        <div class="flex flex-wrap justify-center gap-3">
            <a href="{{ route('menu') }}" class="rounded-full bg-mango-500 hover:bg-mango-600 text-white font-bold px-8 py-3.5 shadow-soft transition">메뉴 보기</a>
            <a href="{{ route('store') }}" class="rounded-full border-2 border-mango-500 text-mango-700 font-bold px-8 py-3.5 transition hover:bg-mango-50">매장 찾기</a>
        </div>
    </div>
</section>

@endsection
