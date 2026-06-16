@php
    $nav = [
        ['브랜드', route('brand')],
        ['메뉴', route('menu')],
        ['매장안내', route('store')],
        ['창업안내', route('franchise')],
        ['공지사항', route('notice.index')],
    ];
@endphp
<header id="site-header"
        class="fixed top-0 inset-x-0 z-50 transition-all duration-300
               [&.is-solid]:bg-white/90 [&.is-solid]:backdrop-blur [&.is-solid]:shadow-[0_4px_30px_-12px_rgba(0,0,0,0.15)]"
        x-data="{ open: false }">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <div class="flex items-center justify-between h-[72px]">
            {{-- Logo --}}
            <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                <span class="text-2xl">🥭</span>
                <span class="font-black text-xl tracking-tight">
                    <span class="text-mango-500">LEE</span><span class="text-neutral-900 group-[.is-solid]:text-neutral-900">FRIENDS</span>
                </span>
            </a>

            {{-- Desktop nav --}}
            <nav class="hidden lg:flex items-center gap-9">
                @foreach ($nav as [$label, $url])
                    <a href="{{ $url }}"
                       class="nav-underline text-[15px] font-semibold text-neutral-700 hover:text-mango-600 transition">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="hidden lg:flex items-center gap-3">
                <a href="{{ route('franchise') }}#inquiry"
                   class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-mango-500 to-mango-600 px-5 py-2.5 text-sm font-bold text-white shadow-soft hover:brightness-105 active:scale-95 transition">
                    창업문의 <span class="text-base leading-none">→</span>
                </a>
            </div>

            {{-- Mobile toggle --}}
            <button @click="open = !open" class="lg:hidden p-2 -mr-2 text-neutral-800" aria-label="메뉴">
                <svg x-show="!open" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                <svg x-show="open" x-cloak class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </div>

    {{-- Mobile menu --}}
    <div x-show="open" x-cloak x-transition.origin.top
         class="lg:hidden bg-white/95 backdrop-blur border-t border-neutral-100 shadow-xl">
        <nav class="px-5 py-4 flex flex-col">
            @foreach ($nav as [$label, $url])
                <a href="{{ $url }}" class="py-3 text-[17px] font-semibold text-neutral-800 border-b border-neutral-100">{{ $label }}</a>
            @endforeach
            <a href="{{ route('franchise') }}#inquiry"
               class="mt-4 text-center rounded-full bg-gradient-to-r from-mango-500 to-mango-600 px-5 py-3 font-bold text-white">창업문의 하기</a>
        </nav>
    </div>

    <style>
        /* nav link color over transparent hero */
        #site-header:not(.is-solid) nav a { color: rgba(255,255,255,.92); }
        #site-header:not(.is-solid) nav a:hover { color: #fff; }
        #site-header:not(.is-solid) .text-neutral-900 { color: #fff; }
        #site-header:not(.is-solid) button { color: #fff; }
    </style>
</header>

{{-- Alpine.js --}}
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
