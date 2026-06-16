@extends('layouts.app')

@section('title', '창업 안내 · LEEFRIENDS')

@section('content')

@include('partials.page-hero', [
    'eyebrow' => 'FRANCHISE',
    'title' => '리프렌즈 창업 안내',
    'subtitle' => '검증된 브랜드와 함께하는 디저트 카페 창업',
])

{{-- why --}}
<section class="py-24">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-14 reveal">
            <p class="text-mango-600 font-bold tracking-widest text-sm mb-3">WHY LEEFRIENDS</p>
            <h2 class="text-3xl md:text-5xl font-black text-neutral-900">리프렌즈를 선택하는 이유</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
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

{{-- process --}}
<section class="py-24 bg-mango-50">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-14 reveal">
            <p class="text-mango-600 font-bold tracking-widest text-sm mb-3">PROCESS</p>
            <h2 class="text-3xl md:text-5xl font-black text-neutral-900">창업 진행 절차</h2>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-4">
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

{{-- cost table --}}
<section class="py-24">
    <div class="max-w-4xl mx-auto px-5 lg:px-8">
        <div class="text-center mb-12 reveal">
            <p class="text-mango-600 font-bold tracking-widest text-sm mb-3">COST</p>
            <h2 class="text-3xl md:text-5xl font-black text-neutral-900">창업 비용 안내</h2>
            <p class="text-neutral-500 mt-4">기준: 전용 50㎡ (15평) / VAT 별도 · 점포 임차료 제외</p>
        </div>
        <div class="reveal rounded-3xl overflow-hidden shadow-card border border-neutral-100">
            <table class="w-full text-left">
                <tbody class="divide-y divide-neutral-100">
                    @foreach ([
                        ['가맹비','1,000만원','브랜드 사용권 및 영업권'],
                        ['교육비','300만원','운영·조리 표준 교육'],
                        ['인테리어','3,500만원','평당 약 230만원 기준'],
                        ['기기·집기','2,200만원','빙수기·쇼케이스 등 주방설비'],
                        ['초도물품','500만원','오픈 초도 식자재'],
                    ] as [$item,$cost,$desc])
                        <tr class="hover:bg-mango-50/50 transition">
                            <td class="px-6 py-5 font-extrabold text-neutral-900 w-40">{{ $item }}</td>
                            <td class="px-6 py-5 font-black text-mango-700 text-lg whitespace-nowrap">{{ $cost }}</td>
                            <td class="px-6 py-5 text-sm text-neutral-500">{{ $desc }}</td>
                        </tr>
                    @endforeach
                    <tr class="bg-neutral-900 text-white">
                        <td class="px-6 py-6 font-black text-lg">합계</td>
                        <td class="px-6 py-6 font-black text-mango-300 text-2xl whitespace-nowrap" colspan="2">약 7,500만원</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="text-center text-xs text-neutral-400 mt-4">* 상기 비용은 참고용이며, 점포 면적·지역·현장 여건에 따라 달라질 수 있습니다.</p>
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
            전화 문의 <a href="tel:1600-0000" class="font-black underline underline-offset-4">1600-0000</a> &nbsp;|&nbsp; 평일 09:00 - 18:00
        </p>
    </div>
</section>

@endsection
