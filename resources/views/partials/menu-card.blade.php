@php
    $badgeMap = [
        'best' => ['BEST', 'bg-mango-600'],
        'new'  => ['NEW', 'bg-emerald-500'],
        'hot'  => ['HOT', 'bg-rose-500'],
    ];
@endphp
<div class="group relative rounded-3xl overflow-hidden bg-white shadow-card hover:shadow-soft transition-all duration-300 hover:-translate-y-1.5">
    <div class="aspect-[4/3] overflow-hidden bg-mango-50">
        <img src="{{ asset($menu->image) }}" alt="{{ $menu->name }}" loading="lazy"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
    </div>
    @if ($menu->badge && isset($badgeMap[$menu->badge]))
        <span class="absolute top-4 left-4 {{ $badgeMap[$menu->badge][1] }} text-white text-xs font-bold px-3 py-1 rounded-full shadow">
            {{ $badgeMap[$menu->badge][0] }}
        </span>
    @endif
    <div class="p-5">
        <p class="text-xs font-semibold text-mango-600 mb-1">{{ $menu->category_label }}</p>
        <h3 class="text-lg font-extrabold text-neutral-900">{{ $menu->name }}</h3>
        @if ($menu->name_en)
            <p class="text-[11px] tracking-wide text-neutral-400 uppercase">{{ $menu->name_en }}</p>
        @endif
        @if ($menu->description)
            <p class="mt-2 text-sm text-neutral-500 line-clamp-2 leading-relaxed">{{ $menu->description }}</p>
        @endif
        <p class="mt-3 text-mango-700 font-black text-lg">{{ number_format($menu->price) }}<span class="text-sm font-bold">원</span></p>
    </div>
</div>
