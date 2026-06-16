@php
    $map = [
        'pending' => 'bg-neutral-100 text-neutral-600',
        'processing' => 'bg-amber-100 text-amber-700',
        'shipping' => 'bg-sky-100 text-sky-700',
        'completed' => 'bg-emerald-100 text-emerald-700',
        'canceled' => 'bg-rose-100 text-rose-600',
    ];
@endphp
<span class="inline-block text-xs font-bold px-2.5 py-1 rounded-full {{ $map[$status] ?? 'bg-neutral-100 text-neutral-600' }}">{{ $label }}</span>
