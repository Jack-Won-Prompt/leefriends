@extends('layouts.app')

@section('title', '창업 안내 · LEEFRIENDS')

@section('content')

{{-- hero --}}
<section class="relative pt-[72px] bg-gradient-to-br from-mango-400 via-mango-500 to-mango-600 overflow-hidden">
    <div class="absolute -top-10 right-6 text-[13rem] opacity-20 select-none animate-floaty">🥭</div>
    <div class="absolute bottom-0 left-1/4 w-80 h-80 rounded-full bg-white/10 blur-3xl"></div>
    <div class="relative max-w-7xl mx-auto px-5 lg:px-8 py-20 lg:py-28 text-center text-white">
        <p class="font-bold tracking-[0.25em] text-sm text-white/85 mb-4 animate-fadeup">MANGOJEONG FRANCHISE</p>
        <h1 class="text-4xl md:text-6xl font-black leading-tight animate-fadeup">
            사계절 프리미엄 망고빙수<br>검증된 프랜차이즈, <span class="text-neutral-900">망고정</span>
        </h1>
        <p class="mt-5 text-lg md:text-xl text-white/90 animate-fadeup">낮은 창업비 · 높은 수익률 · 본사 토탈 지원으로 안정적인 창업을 시작하세요.</p>

        {{-- CTA --}}
        <div class="mt-8 flex flex-wrap items-center justify-center gap-3 animate-fadeup">
            <a href="#inquiry" class="rounded-2xl bg-neutral-900 hover:bg-neutral-800 text-white font-black px-8 py-4 shadow-soft transition">창업 문의하기</a>
            <a href="tel:031-853-1944" class="rounded-2xl bg-white/15 hover:bg-white/25 backdrop-blur text-white font-bold px-8 py-4 transition">📞 전화 상담</a>
        </div>

        {{-- key stats --}}
        <div class="mt-12 grid grid-cols-3 gap-3 max-w-2xl mx-auto animate-fadeup">
            @foreach ([['월 평균 매출','3,000만원+'],['영업 마진','28%'],['창업 비용','4,225만원~']] as [$l,$v])
                <div class="rounded-2xl bg-white/10 border border-white/15 py-5 px-2">
                    <p class="text-xs text-white/70 font-bold">{{ $l }}</p>
                    <p class="mt-1 text-xl md:text-2xl font-black">{{ $v }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- why --}}
<section class="py-24">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-14 reveal">
            <p class="text-mango-600 font-bold tracking-widest text-sm mb-3">WHY LEEFRIENDS</p>
            <h2 class="text-3xl md:text-5xl font-black text-neutral-900">리프렌즈를 선택하는 이유</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 stagger">
            @foreach ([
                ['🍧','차별화된 경쟁력','애플망고 특화 메뉴로 명확한 브랜드 정체성'],
                ['📈','사계절 안정 매출','여름 빙수 + 겨울 디저트로 비수기 없는 운영'],
                ['🤝','체계적인 본사 지원','상권분석·인테리어·교육·마케팅 토탈 케어'],
                ['💡','간편한 운영 시스템','표준화된 레시피로 누구나 쉽게 운영'],
            ] as [$ico,$t,$d])
                <div class="reveal rounded-3xl bg-white shadow-card p-7 hover:shadow-soft hover:-translate-y-1 transition">
                    <div class="text-4xl mb-4">{{ $ico }}</div>
                    <h3 class="text-lg font-extrabold text-neutral-900 mb-2">{{ $t }}</h3>
                    <p class="text-neutral-500 text-sm leading-relaxed">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- revenue / profitability (data visualization) --}}
<section id="revenue" class="py-24 bg-neutral-900 text-white overflow-hidden">
    <style>
        .fr-bar{transform:scaleY(0);transform-origin:bottom;transition:transform 1.1s cubic-bezier(.2,.7,.2,1)}
        .reveal.in .fr-bar{transform:scaleY(1)}
        .fr-gauge-fill{stroke-dasharray:339.29;stroke-dashoffset:339.29;transition:stroke-dashoffset 1.4s cubic-bezier(.2,.7,.2,1)}
        .reveal.in .fr-gauge-fill{stroke-dashoffset:var(--off)}
    </style>
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-14 reveal">
            <p class="text-mango-400 font-bold tracking-widest text-sm mb-3">REVENUE</p>
            <h2 class="text-3xl md:text-5xl font-black">데이터로 검증된 수익성</h2>
            <p class="text-white/60 mt-4">사계절 안정적인 매출과 높은 수익률로 빠른 투자 회수를 목표로 합니다.</p>
        </div>

        {{-- count-up 핵심 지표 --}}
        <div class="grid sm:grid-cols-3 gap-6 max-w-4xl mx-auto stagger">
            @foreach ([
                ['월 평균 매출','3000','만원+','매장 평균 월매출'],
                ['판매 순이익','30','%','홀 · 배달 판매 기준'],
                ['영업 마진','28','%','평균 영업 마진율'],
            ] as [$label,$num,$suffix,$desc])
                <div class="reveal rv-scale rounded-3xl bg-white/5 border border-white/10 px-4 py-8 text-center">
                    <p class="text-sm font-bold text-white/60">{{ $label }}</p>
                    <p class="mt-2 text-3xl md:text-4xl font-black text-mango-300 whitespace-nowrap"><span data-countup="{{ $num }}" data-suffix="{{ $suffix }}">0{{ $suffix }}</span></p>
                    <p class="mt-2 text-xs text-white/50">{{ $desc }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-16 grid lg:grid-cols-2 gap-8">
            {{-- 막대 차트: 모델 매장 월 매출 --}}
            <div class="reveal rv-left rounded-3xl bg-white/5 border border-white/10 p-8">
                <h3 class="font-extrabold text-lg mb-1">모델 매장 월 매출</h3>
                <p class="text-xs text-white/50 mb-8">단위: 만원</p>
                <div class="flex items-end justify-around gap-4 h-56">
                    @foreach ([['다산점',3800],['고암점',3500],['공릉점',3200],['월계점',3000]] as [$store,$sales])
                        <div class="flex-1 flex flex-col items-center justify-end h-full">
                            <span class="text-sm font-black text-mango-300 mb-2">{{ number_format($sales) }}</span>
                            <div class="fr-bar w-full max-w-[64px] rounded-t-xl bg-gradient-to-t from-mango-600 to-mango-400" style="height: {{ round($sales / 4000 * 100) }}%"></div>
                            <span class="mt-3 text-xs text-white/60 font-bold">{{ $store }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 도넛 게이지: 수익률 --}}
            <div class="reveal rv-right rounded-3xl bg-white/5 border border-white/10 p-8">
                <h3 class="font-extrabold text-lg mb-1">수익률</h3>
                <p class="text-xs text-white/50 mb-6">홀·배달 순이익 / 영업 마진</p>
                <div class="grid grid-cols-2 gap-6 place-items-center">
                    @foreach ([['판매 순이익',30,'237.5'],['영업 마진',28,'244.3']] as [$gl,$gv,$off])
                        <div class="text-center">
                            <div class="relative w-36 h-36">
                                <svg viewBox="0 0 128 128" class="w-full h-full -rotate-90">
                                    <circle cx="64" cy="64" r="54" fill="none" stroke="rgba(255,255,255,.1)" stroke-width="12"/>
                                    <circle cx="64" cy="64" r="54" fill="none" stroke="url(#g{{ $loop->index }})" stroke-width="12" stroke-linecap="round" class="fr-gauge-fill" style="--off: {{ $off }}"/>
                                    <defs><linearGradient id="g{{ $loop->index }}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FFD37A"/><stop offset="1" stop-color="#FF8A3D"/></linearGradient></defs>
                                </svg>
                                <div class="absolute inset-0 grid place-items-center">
                                    <span class="text-3xl font-black text-mango-300"><span data-countup="{{ $gv }}" data-suffix="%">0%</span></span>
                                </div>
                            </div>
                            <p class="mt-3 text-sm font-bold text-white/70">{{ $gl }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-white/40 mt-8">* 상기 수치는 운영 매장 평균 기준이며, 상권·입지·운영 방식에 따라 달라질 수 있습니다.</p>
    </div>
</section>


{{-- support --}}
<section class="py-24">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-14 reveal">
            <p class="text-mango-600 font-bold tracking-widest text-sm mb-3">SUPPORT</p>
            <h2 class="text-3xl md:text-5xl font-black text-neutral-900">본사 토탈 지원 시스템</h2>
            <p class="text-neutral-500 mt-4">창업부터 운영까지, 초보 점주도 안심하고 시작할 수 있습니다.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 stagger">
            @foreach ([
                ['🥭','원물·식자재 공급','검증된 애플망고와 표준 식자재를 본사에서 안정적으로 공급'],
                ['🚚','물류·배송 시스템','발주부터 입고까지 체계적으로 관리되는 통합 물류'],
                ['📖','레시피·운영 매뉴얼','표준화된 레시피로 누구나 동일한 맛과 품질 구현'],
                ['🎓','교육·오픈 지원','운영·조리 교육과 오픈 현장 밀착 지원'],
                ['📣','브랜드 마케팅','SNS·프로모션 등 본사 주도 브랜드 마케팅'],
                ['🛠️','상시 슈퍼바이징','QSC 관리와 지속적인 매장 운영 컨설팅'],
            ] as [$ico,$t,$d])
                <div class="reveal rounded-3xl bg-white shadow-card p-7 hover:shadow-soft hover:-translate-y-1 transition">
                    <div class="text-4xl mb-4">{{ $ico }}</div>
                    <h3 class="text-lg font-extrabold text-neutral-900 mb-2">{{ $t }}</h3>
                    <p class="text-neutral-500 text-sm leading-relaxed">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- process --}}
<section class="py-24 bg-mango-50">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-14 reveal">
            <p class="text-mango-600 font-bold tracking-widest text-sm mb-3">PROCESS</p>
            <h2 class="text-3xl md:text-5xl font-black text-neutral-900">창업 진행 절차</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4 stagger">
            @foreach ([
                ['01','창업 상담','온라인/전화 문의 및 1:1 상담'],
                ['02','상권 분석','입지 조사 및 수익성 분석'],
                ['03','가맹 계약','계약 체결 및 점포 확정'],
                ['04','인테리어·교육','매장 시공 및 운영 교육'],
                ['05','오픈','그랜드 오픈 및 운영 지원'],
            ] as [$no,$t,$d])
                <div class="reveal relative rounded-2xl bg-white p-6 text-center shadow-card">
                    <div class="mx-auto w-12 h-12 rounded-full bg-gradient-to-br from-mango-400 to-mango-600 text-white font-black grid place-items-center mb-3">{{ $no }}</div>
                    <h3 class="font-extrabold text-neutral-900">{{ $t }}</h3>
                    <p class="text-xs text-neutral-500 mt-1.5 leading-relaxed">{{ $d }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- packages --}}
@php
    $packages = [
        ['size' => '8~10평', 'total' => '4,225만원', 'items' => [
            ['인테리어', '1,500만원'], ['눈꽃빙수기 (스노우반) 1대', '430만원 (VAT별도)'],
            ['커피머신 1대', '450만원'], ['그라인더', '120만원'], ['디스펜서', '120만원'],
            ['쇼케이스 냉장고', '150만원'], ['냉장고 2대', '70만원'], ['아이스크림 냉동고 2대', '300만원'],
            ['제빙기 (카이저 IMK-3051·50kg)', '85만원'], ['POS', '토스포스 렌탈'],
            ['간판', '350만원'], ['주방집기 일체', '250만원'], ['초기 식자재·물품비', '250만원'], ['의자·테이블', '150만원'],
        ]],
        ['size' => '15평', 'total' => '5,325만원', 'items' => [
            ['인테리어', '2,500만원'], ['눈꽃빙수기 (스노우반) 1대', '430만원 (VAT별도)'],
            ['커피머신 1대', '450만원'], ['그라인더', '120만원'], ['디스펜서', '120만원'],
            ['쇼케이스 냉장고', '150만원'], ['냉장고 2대', '70만원'], ['아이스크림 냉동고 2대', '300만원'],
            ['제빙기 (카이저 IMK-3051·50kg)', '85만원'], ['POS', '토스포스 렌탈'],
            ['간판', '350만원'], ['주방집기 일체', '250만원'], ['초기 식자재·물품비', '250만원'], ['의자·테이블', '250만원'],
        ]],
        ['size' => '30평', 'total' => '13,180만원', 'items' => [
            ['인테리어', '6,000만원'], ['눈꽃빙수기 (스노우반) 2대', '860만원 (VAT별도)'],
            ['커피머신 1대 (3그룹)', '800만원'], ['그라인더 2대', '240만원'], ['디스펜서 2대', '240만원'],
            ['쇼케이스 냉장고 2대', '300만원'], ['냉장고 4대', '140만원'], ['아이스크림 냉동고 4대', '600만원'],
            ['제빙기 (카이저·100kg)', '200만원'], ['POS', '토스포스 렌탈'],
            ['간판', '1,000만원'], ['초기 식자재·물품비', '500만원'], ['의자·테이블', '1,500만원'], ['주방집기 및 소도구', '800만원'],
        ]],
    ];
@endphp
<section class="py-24">
    <div class="max-w-5xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-12 reveal">
            <p class="text-mango-600 font-bold tracking-widest text-sm mb-3">PACKAGE</p>
            <h2 class="text-3xl md:text-5xl font-black text-neutral-900">추천 창업 패키지 <span class="text-neutral-400 text-2xl">(예시)</span></h2>
            <p class="text-neutral-500 mt-4">점포 규모에 맞춘 창업 패키지 · VAT 별도 · 아래 «별도 비용» 제외</p>
        </div>

        {{-- 탭 --}}
        <div class="reveal flex flex-wrap justify-center gap-2 mb-8">
            @foreach ($packages as $i => $p)
                <button type="button" data-pkg-tab="{{ $i }}"
                        class="pkg-tab rounded-full px-6 py-3 font-black text-sm transition {{ $i === 0 ? 'bg-mango-500 text-white shadow-soft' : 'bg-white border border-neutral-200 text-neutral-600 hover:border-mango-300' }}">
                    {{ $p['size'] }} <span class="opacity-80 font-bold">· {{ $p['total'] }}</span>
                </button>
            @endforeach
        </div>

        {{-- 패널 --}}
        @foreach ($packages as $i => $p)
            <div data-pkg-panel="{{ $i }}" class="reveal {{ $i === 0 ? '' : 'hidden' }}">
                <div class="rounded-3xl overflow-hidden shadow-card border border-neutral-100 bg-white">
                    <div class="flex items-center justify-between px-6 py-5 bg-neutral-900 text-white">
                        <span class="font-black text-lg">{{ $p['size'] }} 패키지</span>
                        <span class="font-black text-mango-300 text-2xl">{{ $p['total'] }}</span>
                    </div>
                    <div class="grid sm:grid-cols-2 gap-x-8">
                        @foreach ($p['items'] as $idx => [$item, $cost])
                            <div class="flex items-center justify-between gap-3 px-6 py-3.5 {{ $idx > 0 ? 'border-t border-neutral-100' : '' }} sm:border-t hover:bg-mango-50/40">
                                <span class="text-sm font-semibold text-neutral-700">{{ $item }}</span>
                                <span class="text-sm font-black text-mango-700 whitespace-nowrap">{{ $cost }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <p class="text-center text-xs text-neutral-500 mt-4">💡 커피머신 외 사이드 용품·냉장/냉동고는 <b>중고 가능</b> (본사 협의)</p>
            </div>
        @endforeach

        {{-- 별도 비용 (공통) --}}
        <div class="reveal mt-10 rounded-3xl bg-mango-50 border border-mango-100 p-7">
            <h3 class="font-extrabold text-neutral-900 mb-4">별도 비용 안내</h3>
            <div class="flex flex-wrap gap-2.5">
                @foreach (['점포 보증금','권리금 (있는 경우)','임대료','상표권 사용료 550만원','교육비 250만원','초도 홍보비'] as $x)
                    <span class="rounded-full bg-white border border-neutral-200 text-neutral-600 text-sm font-bold px-4 py-2">{{ $x }}</span>
                @endforeach
            </div>
        </div>

        <p class="text-center text-xs text-neutral-400 mt-6">* 상기 패키지는 예시이며, 점포 면적·지역·현장 여건에 따라 달라질 수 있습니다.</p>
    </div>
</section>
@push('scripts')
<script>
    document.querySelectorAll('[data-pkg-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idx = btn.dataset.pkgTab;
            document.querySelectorAll('[data-pkg-tab]').forEach(function (b) {
                var on = b.dataset.pkgTab === idx;
                b.classList.toggle('bg-mango-500', on); b.classList.toggle('text-white', on); b.classList.toggle('shadow-soft', on);
                b.classList.toggle('bg-white', !on); b.classList.toggle('border', !on); b.classList.toggle('border-neutral-200', !on); b.classList.toggle('text-neutral-600', !on);
            });
            document.querySelectorAll('[data-pkg-panel]').forEach(function (p) {
                p.classList.toggle('hidden', p.dataset.pkgPanel !== idx);
            });
        });
    });
</script>
@endpush

{{-- testimonials --}}
<section class="py-24 bg-mango-50">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-14 reveal">
            <p class="text-mango-600 font-bold tracking-widest text-sm mb-3">STORY</p>
            <h2 class="text-3xl md:text-5xl font-black text-neutral-900">가맹점주 이야기</h2>
            <p class="text-neutral-500 mt-4">먼저 시작한 점주님들의 생생한 경험을 들어보세요.</p>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 stagger">
            @foreach ([
                ['본사에서 원물과 레시피를 다 챙겨줘서, 요식업이 처음인데도 어렵지 않게 시작했어요.','김○○ 점주','망고정 다산점'],
                ['여름엔 빙수, 겨울엔 디저트로 사계절 매출이 꾸준한 게 가장 큰 장점입니다.','이○○ 점주','망고정 고암점'],
                ['오픈 준비부터 운영까지 밀착 지원을 받아 안정적으로 자리 잡을 수 있었습니다.','박○○ 점주','망고정 공릉점'],
            ] as [$quote,$name,$store])
                <div class="reveal rounded-3xl bg-white shadow-card p-8 flex flex-col">
                    <div class="text-mango-400 text-5xl font-black leading-none mb-3">“</div>
                    <p class="text-neutral-700 leading-relaxed flex-1">{{ $quote }}</p>
                    <div class="mt-6 pt-5 border-t border-neutral-100">
                        <p class="font-extrabold text-neutral-900">{{ $name }}</p>
                        <p class="text-sm text-mango-600 font-bold">{{ $store }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===================== INQUIRY FORM ===================== --}}
<section id="inquiry" class="py-24 bg-gradient-to-br from-mango-500 to-mango-600 scroll-mt-20">
    <div class="max-w-3xl mx-auto px-5 lg:px-8">
        <div class="text-center text-white mb-10 reveal">
            <p class="font-bold tracking-widest text-sm text-white/85 mb-3">INQUIRY</p>
            <h2 class="text-3xl md:text-5xl font-black">온라인 창업 문의</h2>
            <p class="mt-4 text-white/90">아래 정보를 남겨주시면 담당자가 빠르게 연락드리겠습니다.</p>
        </div>

        <div class="bg-white rounded-3xl shadow-soft p-7 md:p-10 reveal">
            @if ($errors->any())
                <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 px-5 py-4 text-sm text-rose-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('franchise.store') }}" class="space-y-5">
                @csrf
                <div class="grid md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-2">성함 <span class="text-mango-600">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="홍길동">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-2">연락처 <span class="text-mango-600">*</span></label>
                        <input type="text" name="phone" value="{{ old('phone') }}" required
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="010-1234-5678">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-2">이메일</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="example@email.com">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-2">희망 창업지역</label>
                        <input type="text" name="region" value="{{ old('region') }}"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="서울 강남구">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-2">창업 예산</label>
                    <select name="budget" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                        <option value="">선택해 주세요</option>
                        @foreach (['5천만원 이하','5천만원 ~ 1억원','1억원 ~ 1.5억원','1.5억원 이상'] as $b)
                            <option value="{{ $b }}" @selected(old('budget') === $b)>{{ $b }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-2">문의 내용</label>
                    <textarea name="message" rows="4"
                              class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="궁금하신 점을 자유롭게 남겨주세요.">{{ old('message') }}</textarea>
                </div>

                <label class="flex items-start gap-3 rounded-xl bg-neutral-50 p-4 cursor-pointer">
                    <input type="checkbox" name="agree_privacy" value="1" @checked(old('agree_privacy'))
                           class="mt-0.5 rounded text-mango-500 focus:ring-mango-400">
                    <span class="text-sm text-neutral-600">
                        <span class="font-bold text-neutral-800">[필수]</span> 개인정보 수집 및 이용에 동의합니다.
                        수집된 정보는 창업 상담 목적으로만 활용되며, 상담 완료 후 안전하게 폐기됩니다.
                    </span>
                </label>

                <button type="submit"
                        class="w-full rounded-xl bg-gradient-to-r from-mango-500 to-mango-600 text-white font-black text-lg py-4 shadow-soft hover:brightness-105 active:scale-[0.99] transition">
                    창업 문의 보내기
                </button>
            </form>
        </div>

        <p class="text-center text-white/90 mt-8 reveal">
            전화 문의 <a href="tel:031-853-1944" class="font-black underline underline-offset-4">031-853-1944</a> &nbsp;|&nbsp; 평일 09:00 - 18:00
        </p>
    </div>
</section>

{{-- floating consultation CTA --}}
<div class="fixed bottom-5 right-5 z-40 flex flex-col items-end gap-2.5 animate-fadeup">
    <a href="tel:031-853-1944" class="group flex items-center gap-2 rounded-full bg-neutral-900 text-white font-bold pl-4 pr-5 py-3 shadow-soft hover:bg-neutral-800 hover:scale-105 transition">
        <span class="text-lg animate-floaty">📞</span>
        <span class="text-sm leading-tight">가맹문의<br><span class="font-black">031-853-1944</span></span>
    </a>
    <a href="#inquiry" class="relative rounded-full bg-mango-500 hover:bg-mango-600 text-white font-black px-6 py-3.5 shadow-soft hover:scale-105 transition text-sm">
        <span class="absolute inset-0 rounded-full bg-mango-400 opacity-60 animate-ping"></span>
        <span class="relative">창업 문의하기</span>
    </a>
</div>

@endsection
