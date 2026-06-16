<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', '관리자') · LEEFRIENDS Admin</title>
    <link rel="icon" href="{{ asset('images/menu/mango-cheese-bingsu.svg') }}">
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { sans: ['Pretendard Variable','Pretendard','sans-serif'] },
                colors: { mango: { 50:'#FFF9ED',100:'#FFF1D2',400:'#FFB23D',500:'#FF9F1C',600:'#F2784B',700:'#D45A1F' } },
            }},
        }
    </script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="font-sans bg-neutral-100 text-neutral-800" x-data="{ sidebar: false }">
@php
    $menu = [
        ['admin.dashboard', '대시보드', '📊', []],
        ['admin.inquiries.index', '창업문의', '📩', ['admin.inquiries.show']],
        ['admin.notices.index', '공지사항', '📢', ['admin.notices.create','admin.notices.edit']],
        ['admin.menus.index', '메뉴관리', '🍧', ['admin.menus.create','admin.menus.edit']],
        ['admin.stores.index', '매장관리', '🏬', ['admin.stores.create','admin.stores.edit']],
    ];
@endphp

<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="hidden lg:flex w-64 shrink-0 flex-col bg-neutral-900 text-neutral-300">
        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 h-16 px-6 border-b border-white/10">
            <span class="text-2xl">🥭</span>
            <span class="font-black text-white"><span class="text-mango-400">LEE</span>FRIENDS</span>
            <span class="text-[10px] font-bold text-mango-400 bg-white/10 px-1.5 py-0.5 rounded">ADMIN</span>
        </a>
        <nav class="flex-1 p-4 space-y-1">
            @foreach ($menu as [$route, $label, $icon, $also])
                @php $active = request()->routeIs($route) || collect($also)->contains(fn($r) => request()->routeIs($r)); @endphp
                <a href="{{ route($route) }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold transition
                          {{ $active ? 'bg-mango-500 text-white' : 'hover:bg-white/5 hover:text-white' }}">
                    <span>{{ $icon }}</span> {{ $label }}
                </a>
            @endforeach
        </nav>
        <div class="p-4 border-t border-white/10">
            <a href="{{ route('home') }}" target="_blank" class="block px-4 py-2 text-sm hover:text-white">홈페이지 보기 ↗</a>
            <form method="POST" action="{{ route('admin.logout') }}">@csrf
                <button class="w-full text-left px-4 py-2 text-sm text-rose-400 hover:text-rose-300">로그아웃</button>
            </form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        {{-- Topbar --}}
        <header class="h-16 bg-white border-b border-neutral-200 flex items-center justify-between px-5 lg:px-8 sticky top-0 z-30">
            <h1 class="text-lg font-extrabold text-neutral-900">@yield('title', '관리자')</h1>
            <div class="flex items-center gap-3 text-sm">
                <span class="text-neutral-500">{{ auth()->user()->name }} 님</span>
                <form method="POST" action="{{ route('admin.logout') }}">@csrf
                    <button class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1.5 font-semibold">로그아웃</button>
                </form>
            </div>
        </header>

        <main class="flex-1 p-5 lg:p-8">
            @if (session('success'))
                <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-3.5 text-sm font-medium">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-5 py-3.5 text-sm font-medium">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-5 py-3.5 text-sm">
                    <ul class="list-disc list-inside space-y-1">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@stack('scripts')
</body>
</html>
