{{-- 화면 타이틀은 상단바(@yield('title'))에서만 표시한다. 콘텐츠 상단의 부제(설명) 텍스트는
     제거하고, 액션 버튼(있을 때)만 우측에 남긴다. --}}
@props(['title' => null, 'subtitle' => null, 'icon' => null])

@isset($actions)
    <div class="flex flex-wrap items-center justify-end gap-2 mb-5">
        {{ $actions }}
    </div>
@endisset
