@props(['action' => null, 'title' => '검색 조건', 'open' => true])

{{-- fulfillment 스타일 «검색 조건» 카드 (GET 폼, 접기/펼치기) --}}
<form method="GET" action="{{ $action }}" x-data="{ open: {{ $open ? 'true' : 'false' }} }"
      {{ $attributes->merge(['class' => 'rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden mb-5']) }}>
    <div class="flex items-center justify-between px-5 py-3 border-b border-neutral-100 bg-neutral-50">
        <span class="font-extrabold text-neutral-800 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 text-mango-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M6 8h12M9 12h6M11 16h2"/></svg>
            {{ $title }}
        </span>
        <div class="flex items-center gap-2">
            <a href="{{ url()->current() }}" class="inline-flex items-center gap-1 rounded-lg bg-white border border-neutral-200 px-3 py-1.5 text-xs font-bold text-neutral-500 hover:bg-neutral-100">초기화</a>
            <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-mango-500 hover:bg-mango-600 px-4 py-1.5 text-xs font-bold text-white">검색</button>
            <button type="button" @click="open = !open" class="w-7 h-7 grid place-items-center rounded-lg text-neutral-400 hover:bg-neutral-100">
                <svg class="w-4 h-4 transition-transform" :class="open ? '' : 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 15l6-6 6 6"/></svg>
            </button>
        </div>
    </div>
    <div x-show="open" x-collapse>
        <div class="p-5 grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-5 gap-y-4">
            {{ $slot }}
        </div>
    </div>
</form>
