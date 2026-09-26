@props(['items' => []])

{{-- 배민식 리스트 상단 요약 카드 — items: [['label','value','variant','href'?,'unit'?], ...]
     variant: accent|info|success|warn|danger|default (좌측 색 바 + 숫자 색) --}}
@php
    $palette = [
        'accent' => ['bar' => 'bg-mango-500', 'num' => 'text-mango-600'],
        'info' => ['bar' => 'bg-sky-500', 'num' => 'text-sky-600'],
        'success' => ['bar' => 'bg-emerald-500', 'num' => 'text-emerald-600'],
        'warn' => ['bar' => 'bg-amber-500', 'num' => 'text-amber-600'],
        'danger' => ['bar' => 'bg-rose-500', 'num' => 'text-rose-600'],
        'default' => ['bar' => 'bg-neutral-400', 'num' => 'text-neutral-800'],
    ];
    $cols = min(max(count($items), 1), 5);
    $colCls = [1 => 'sm:grid-cols-1', 2 => 'sm:grid-cols-2', 3 => 'sm:grid-cols-3', 4 => 'sm:grid-cols-4', 5 => 'sm:grid-cols-5'][$cols];
@endphp
<div class="grid grid-cols-2 {{ $colCls }} gap-3 mb-3">
    @foreach ($items as $it)
        @php
            $v = $palette[$it['variant'] ?? 'default'] ?? $palette['default'];
            $val = $it['value'] ?? 0;
            $href = $it['href'] ?? null;
            $tag = $href ? 'a' : 'div';
        @endphp
        <{{ $tag }} @if ($href) href="{{ $href }}" data-ws-tab @endif
           class="relative flex items-center gap-3 rounded-2xl bg-white border border-neutral-100 shadow-sm pl-5 pr-4 py-3.5 {{ $href ? 'hover:shadow-md hover:border-neutral-200 transition' : '' }}">
            <span class="absolute left-0 top-3 bottom-3 w-1 rounded-r {{ $v['bar'] }}"></span>
            <div class="min-w-0">
                <p class="text-xs text-neutral-500 font-medium truncate">{{ $it['label'] }}</p>
                <p class="text-xl font-black {{ ($val && $val !== '0') ? $v['num'] : 'text-neutral-300' }} mt-0.5 leading-none">
                    {{ is_numeric($val) ? number_format($val) : $val }}<span class="text-xs font-bold text-neutral-400 ml-0.5">{{ $it['unit'] ?? '건' }}</span>
                </p>
            </div>
        </{{ $tag }}>
    @endforeach
</div>
