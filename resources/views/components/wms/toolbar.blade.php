@props(['count' => null, 'label' => '검색 결과'])

{{-- 결과 툴바: 좌측 «검색 결과 N건» + 우측 액션 슬롯 --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-3">
    <div class="flex items-center gap-2 text-sm">
        <span class="font-extrabold text-neutral-800">{{ $label }}</span>
        @if (! is_null($count))
            <span class="font-black text-mango-600">{{ number_format($count) }}</span><span class="text-neutral-400">건</span>
        @endif
    </div>
    <div class="flex items-center gap-2">
        {{ $slot }}
    </div>
</div>
