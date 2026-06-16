@props(['title', 'subtitle' => null, 'icon' => null])

<div class="flex flex-wrap items-start justify-between gap-3 mb-5">
    <div class="flex items-center gap-3">
        @if ($icon)
            <div class="w-11 h-11 rounded-2xl bg-mango-100 text-mango-600 grid place-items-center text-xl shrink-0">{{ $icon }}</div>
        @endif
        <div>
            <h1 class="text-xl font-black text-neutral-900 leading-tight">{{ $title }}</h1>
            @if ($subtitle)
                <p class="text-sm text-neutral-400 mt-0.5">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
