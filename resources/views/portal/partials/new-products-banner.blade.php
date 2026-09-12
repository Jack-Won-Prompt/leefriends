{{-- 금일 등록 신규 품목 배너 — 대시보드 상단. $newProducts=['count','featured'], $href 필요 --}}
@if (! empty($newProducts['count']) && ! empty($newProducts['featured']))
    @php $p = $newProducts['featured']; $others = (int) $newProducts['count'] - 1; @endphp
    <a href="{{ $href }}"
       class="group flex items-center gap-4 mb-6 rounded-2xl border border-mango-200 bg-gradient-to-r from-mango-50 to-white p-4 shadow-sm hover:shadow-md transition">
        <span class="shrink-0 inline-flex items-center gap-1.5 rounded-full bg-mango-500 text-white text-xs font-extrabold px-3 py-1.5 whitespace-nowrap">🆕 금일 등록 품목</span>
        <div class="shrink-0 w-16 h-16 rounded-xl overflow-hidden bg-white border border-mango-100 grid place-items-center">
            @if ($p->image)
                <img src="{{ asset($p->image) }}" alt="{{ $p->name }}" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<span class=\'text-2xl\'>🆕</span>'">
            @else
                <span class="text-2xl">🆕</span>
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-lg font-extrabold text-neutral-900 truncate">
                {{ $p->name }}
                @if ($others > 0)
                    <span class="text-mango-600 font-bold">외 {{ number_format($others) }}건</span>
                @endif
            </p>
            <p class="text-xs text-neutral-500 mt-0.5">오늘 새로 등록된 품목입니다. 클릭해서 확인하세요.</p>
        </div>
        <span class="shrink-0 text-mango-600 font-bold text-sm whitespace-nowrap group-hover:translate-x-0.5 transition">전체 보기 →</span>
    </a>
@endif
