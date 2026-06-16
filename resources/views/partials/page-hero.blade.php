@php
    /** @var string $title @var string $subtitle @var string $eyebrow */
    $eyebrow ??= '';
    $subtitle ??= '';
@endphp
<section class="relative pt-[72px] bg-gradient-to-br from-mango-400 via-mango-500 to-mango-600 overflow-hidden">
    <div class="absolute -top-10 right-10 text-[12rem] opacity-20 select-none animate-floaty">🥭</div>
    <div class="absolute bottom-0 left-1/4 w-72 h-72 rounded-full bg-white/10 blur-3xl"></div>
    <div class="relative max-w-7xl mx-auto px-5 lg:px-8 py-20 lg:py-28 text-center text-white">
        @if ($eyebrow)
            <p class="font-bold tracking-[0.25em] text-sm text-white/85 mb-4 animate-fadeup">{{ $eyebrow }}</p>
        @endif
        <h1 class="text-4xl md:text-6xl font-black animate-fadeup">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-5 text-lg md:text-xl text-white/90 animate-fadeup">{{ $subtitle }}</p>
        @endif
    </div>
</section>
