@props(['label' => null])

<div>
    @if ($label)
        <label class="block text-xs font-bold text-neutral-500 mb-1.5">{{ $label }}</label>
    @endif
    {{ $slot }}
</div>
