@props(['label', 'value', 'icon' => null, 'variant' => 'default', 'sub' => null, 'href' => null])

@php
    $grad = [
        'default' => 'from-neutral-400 to-neutral-500',
        'accent' => 'from-mango-400 to-mango-600',
        'success' => 'from-emerald-400 to-teal-600',
        'warn' => 'from-amber-400 to-orange-500',
        'danger' => 'from-rose-400 to-rose-600',
        'info' => 'from-sky-400 to-indigo-500',
    ][$variant] ?? 'from-neutral-400 to-neutral-500';
    $tag = $href ? 'a' : 'div';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @endif
   class="rounded-2xl bg-white p-5 shadow-sm border border-neutral-100 {{ $href ? 'hover:shadow-md transition' : '' }} block">
    <div class="flex items-start justify-between">
        <p class="text-sm text-neutral-500 font-medium">{{ $label }}</p>
        @if ($icon)
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br {{ $grad }} grid place-items-center text-base shrink-0">{{ $icon }}</div>
        @endif
    </div>
    <p class="text-3xl font-black text-neutral-900 mt-2">{{ $value }}</p>
    @if ($sub)
        <p class="text-xs text-neutral-400 mt-1">{{ $sub }}</p>
    @endif
</{{ $tag }}>
