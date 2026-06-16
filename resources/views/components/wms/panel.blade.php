@props(['title' => null, 'flush' => false])

{{-- 카드 패널: 헤더(타이틀+액션) + 본문 --}}
<div {{ $attributes->merge(['class' => 'rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900 flex items-center gap-2">
                <span class="w-1 h-4 rounded bg-mango-500"></span>{{ $title }}
            </h2>
            @isset($actions)<div class="flex items-center gap-2">{{ $actions }}</div>@endisset
        </div>
    @endif
    <div class="{{ $flush ? '' : 'p-0' }}">{{ $slot }}</div>
</div>
