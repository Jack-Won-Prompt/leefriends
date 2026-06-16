@php
    $map = [
        'pending' => 'bg-neutral-100 text-neutral-600',
        'shipping' => 'bg-sky-100 text-sky-700',
        'delivered' => 'bg-emerald-100 text-emerald-700',
    ];
@endphp
<span class="inline-block text-xs font-bold px-2.5 py-1 rounded-full {{ $map[$status] ?? 'bg-neutral-100 text-neutral-600' }}">{{ $label }}</span>
