{{-- 화면 타이틀은 상단바(@yield('title'))에서만 표시한다. 여기서는 중복 방지를 위해
     타이틀·아이콘을 렌더하지 않고, 부제(subtitle)와 액션 버튼만 남긴다. --}}
@props(['title' => null, 'subtitle' => null, 'icon' => null])

@if ($subtitle || isset($actions))
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="min-w-0">
            @if ($subtitle)
                <p class="text-sm text-neutral-400">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>
@endif
