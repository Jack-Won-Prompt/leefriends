@php
    $map = [
        'new' => 'bg-rose-100 text-rose-600',
        'contacted' => 'bg-amber-100 text-amber-700',
        'done' => 'bg-emerald-100 text-emerald-700',
    ];
@endphp
<span class="inline-block text-xs font-bold px-2.5 py-1 rounded-full {{ $map[$status] ?? 'bg-neutral-100 text-neutral-600' }}">{{ $label }}</span>
