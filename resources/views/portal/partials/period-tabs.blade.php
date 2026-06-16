{{-- 기간 필터. $routeName, $period 필요 / $from, $to (선택) --}}
@php $from = $from ?? request('from'); $to = $to ?? request('to'); $hasRange = $from || $to; @endphp
<div class="flex flex-wrap items-center gap-3 mb-6">
    {{-- 빠른 기간 --}}
    <div class="flex gap-2">
        @foreach (['all' => '전체', 'month' => '이번 달'] as $key => $label)
            <a href="{{ route($routeName, $key === 'all' ? [] : ['period' => $key]) }}"
               class="px-4 py-2 rounded-full text-sm font-bold transition {{ ! $hasRange && $period === $key ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 hover:bg-mango-50' }}">{{ $label }}</a>
        @endforeach
    </div>

    {{-- 직접 기간 검색 (from ~ to) --}}
    <form method="GET" action="{{ route($routeName) }}" class="flex flex-wrap items-center gap-2 ml-auto">
        <input type="date" name="from" value="{{ $from }}" max="{{ $to ?: '' }}"
               class="rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm py-2">
        <span class="text-neutral-400 text-sm">~</span>
        <input type="date" name="to" value="{{ $to }}" min="{{ $from ?: '' }}"
               class="rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm py-2">
        <button class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">조회</button>
        @if ($hasRange)
            <a href="{{ route($routeName) }}" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2 text-sm transition">초기화</a>
        @endif
    </form>
</div>
@if ($hasRange)
    <p class="-mt-3 mb-6 text-sm text-neutral-500">📅 <b class="text-mango-700">{{ $from ?: '처음' }}</b> ~ <b class="text-mango-700">{{ $to ?: '오늘' }}</b> 기간 조회 결과</p>
@endif
