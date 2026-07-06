@extends('portal.layout')
@section('title', '방문 분석')

@section('content')
@php
    $maxDaily = max(1, ...array_values($daily ?: [0]));
    $maxPage = max(1, $topPages->max('c') ?? 1);
    $maxSrc = max(1, $sources->max('c') ?? 1);
    $srcColor = ['naver' => 'bg-emerald-500', 'google' => 'bg-sky-500', 'direct' => 'bg-neutral-500', 'instagram' => 'bg-pink-500', 'youtube' => 'bg-red-500', 'daum' => 'bg-amber-500', 'facebook' => 'bg-indigo-500', 'referral' => 'bg-mango-500'];
@endphp

<x-wms.page-head title="방문 분석" subtitle="사이트 방문수 · 페이지별 방문 · 유입 경로 · 방문 이력을 확인합니다." icon="📊">
    <x-slot:actions>
        <form method="GET" class="flex flex-wrap items-center gap-2">
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-xl border-neutral-200 text-sm py-2">
            <span class="text-neutral-400">~</span>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-xl border-neutral-200 text-sm py-2">
            <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm">조회</button>
        </form>
    </x-slot:actions>
</x-wms.page-head>

{{-- 요약 카드 --}}
<div class="grid sm:grid-cols-3 gap-4 mb-6">
    @foreach ([['총 방문수', $total, '기간 내 페이지뷰'], ['순 방문자', $unique, '중복 제외(세션 기준)'], ['오늘 방문', $today, '오늘 페이지뷰']] as [$label, $val, $desc])
        <x-wms.panel class="p-6">
            <p class="text-sm font-bold text-neutral-500">{{ $label }}</p>
            <p class="mt-1 text-3xl font-black text-mango-700">{{ number_format($val) }}</p>
            <p class="mt-1 text-xs text-neutral-400">{{ $desc }}</p>
        </x-wms.panel>
    @endforeach
</div>

{{-- 일별 추이 --}}
<x-wms.panel class="p-6 mb-6">
    <h3 class="font-extrabold text-neutral-900 mb-5">일별 방문 추이</h3>
    <div class="flex items-end gap-1 h-40">
        @foreach ($daily as $date => $c)
            <div class="flex-1 h-full flex items-end" title="{{ $date }} · {{ number_format($c) }}회">
                <div class="w-full rounded-t bg-gradient-to-t from-mango-500 to-mango-300 hover:from-mango-600" style="height: {{ $c ? max(3, round($c / $maxDaily * 100)) : 0 }}%"></div>
            </div>
        @endforeach
    </div>
    <div class="flex justify-between text-[11px] text-neutral-400 mt-2">
        <span>{{ $from->format('m.d') }}</span><span>{{ $to->format('m.d') }}</span>
    </div>
</x-wms.panel>

<div class="grid lg:grid-cols-2 gap-6 mb-6">
    {{-- 페이지별 방문 순위 --}}
    <x-wms.panel class="p-6">
        <h3 class="font-extrabold text-neutral-900 mb-5">페이지별 방문 순위</h3>
        @forelse ($topPages as $p)
            <div class="mb-3">
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-bold text-neutral-700">{{ $p->page_name ?? $p->path }}</span>
                    <span class="font-black text-mango-700">{{ number_format($p->c) }}</span>
                </div>
                <div class="h-2 rounded-full bg-neutral-100 overflow-hidden">
                    <div class="h-full rounded-full bg-mango-500" style="width: {{ round($p->c / $maxPage * 100) }}%"></div>
                </div>
                <p class="text-[11px] text-neutral-400 mt-0.5">{{ $p->path }}</p>
            </div>
        @empty
            <p class="text-neutral-400 text-sm py-6 text-center">데이터가 없습니다.</p>
        @endforelse
    </x-wms.panel>

    {{-- 유입 경로 --}}
    <x-wms.panel class="p-6">
        <h3 class="font-extrabold text-neutral-900 mb-5">유입 경로</h3>
        @forelse ($sources as $s)
            <div class="mb-3">
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-bold text-neutral-700">{{ \App\Models\PageVisit::SOURCE_LABELS[$s->source] ?? $s->source }}</span>
                    <span class="font-black text-neutral-600">{{ number_format($s->c) }} <span class="text-xs text-neutral-400">({{ $total ? round($s->c / $total * 100) : 0 }}%)</span></span>
                </div>
                <div class="h-2 rounded-full bg-neutral-100 overflow-hidden">
                    <div class="h-full rounded-full {{ $srcColor[$s->source] ?? 'bg-neutral-400' }}" style="width: {{ round($s->c / $maxSrc * 100) }}%"></div>
                </div>
            </div>
        @empty
            <p class="text-neutral-400 text-sm py-6 text-center">데이터가 없습니다.</p>
        @endforelse

        <div class="mt-6 pt-5 border-t border-neutral-100 flex gap-6 text-sm">
            <div><span class="text-neutral-400">📱 모바일</span> <b class="text-neutral-700">{{ number_format($devices['mobile'] ?? 0) }}</b></div>
            <div><span class="text-neutral-400">💻 PC</span> <b class="text-neutral-700">{{ number_format($devices['desktop'] ?? 0) }}</b></div>
        </div>
    </x-wms.panel>
</div>

{{-- 방문 이력 --}}
<x-wms.panel>
    <div class="px-6 py-4 border-b border-neutral-100"><h3 class="font-extrabold text-neutral-900">방문 이력</h3></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-5 py-3">방문 시각</th>
                    <th class="text-left font-semibold px-5 py-3">페이지</th>
                    <th class="text-left font-semibold px-5 py-3">유입 경로</th>
                    <th class="text-left font-semibold px-5 py-3 hidden md:table-cell">기기</th>
                    <th class="text-left font-semibold px-5 py-3 hidden lg:table-cell">방문자</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($recent as $v)
                    <tr class="hover:bg-mango-50/40">
                        <td class="px-5 py-3 whitespace-nowrap text-neutral-500">{{ $v->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="px-5 py-3">
                            <span class="font-bold text-neutral-800">{{ $v->page_name ?? $v->path }}</span>
                            <span class="block text-[11px] text-neutral-400">{{ $v->path }}</span>
                        </td>
                        <td class="px-5 py-3 whitespace-nowrap">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600">{{ \App\Models\PageVisit::SOURCE_LABELS[$v->source] ?? $v->source }}</span>
                            @if ($v->referrer)<span class="block text-[11px] text-neutral-400 mt-0.5">{{ $v->referrer }}</span>@endif
                        </td>
                        <td class="px-5 py-3 hidden md:table-cell text-neutral-500">{{ $v->device === 'mobile' ? '📱 모바일' : '💻 PC' }}</td>
                        <td class="px-5 py-3 hidden lg:table-cell text-neutral-400 font-mono text-xs">{{ substr($v->visitor_hash, 0, 8) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-neutral-400">방문 기록이 없습니다.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-wms.panel>

<div class="mt-5">{{ $recent->links() }}</div>
<p class="mt-4 text-xs text-neutral-400">※ 방문자 식별은 세션 기반 익명 해시이며, IP는 해시로만 저장되어 개인을 식별하지 않습니다.</p>
@endsection
