<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '포털') · LEEFRIENDS 발주포털</title>
    <link rel="icon" href="{{ asset('images/menu/mango-cheese-bingsu.svg') }}">
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['Pretendard Variable','Pretendard','sans-serif'] },
            // 브랜드 색상: ce-admin 틸(teal) 램프. 화면 전반의 mango-* 유틸리티가 그대로 틸로 리테마됨.
            colors: { mango: { 50:'#E9F9FB',100:'#D3F1F7',200:'#A9DCE7',300:'#72BCCC',400:'#4898A9',500:'#28798B',600:'#0B5C6E',700:'#044456',800:'#003847',900:'#022C3A' } },
        }}}
    </script>
    <style>
        /* ===== ce-admin 디자인 토큰 ===== */
        :root{
            --primary:#28798B; --primary-light:#E9F9FB; --primary-dark:#0B5C6E; --primary-accent:#72BCCC;
            --bg:#F3F5F7; --bg-card:#FFFFFF; --bg-secondary:#F9FAFC;
            --border:#E8EAEC; --border-light:#F3F5F7;
            --text-primary:#101317; --text-secondary:#333940; --text-muted:#999EA4;
            --success:#12B76A; --warning:#F59E0B; --danger:#D73D3F; --info:#0EA5E9;
            --radius:8px; --radius-lg:12px;
        }
        [x-cloak]{display:none!important}
        /* 조밀한 어드민 밀도 — 기본 13px */
        html{font-size:13px}
        body{background:var(--bg); color:var(--text-secondary); -webkit-font-smoothing:antialiased; font-size:13px}
        /* 좁은 열에서 헤더·액션 버튼·상태 뱃지 텍스트가 세로로 줄바꿈되지 않도록 */
        table th, table td button, table td .rounded-full { white-space: nowrap; }
        /* 화면 폰트 하나로 통일 — 코드·번호 등 monospace 도 본문 폰트(Pretendard)로 */
        .font-mono, code, pre, kbd, samp { font-family: 'Pretendard Variable', Pretendard, sans-serif !important; }
        /* 플랫 카드 — 그림자 최소화, ce-admin 스타일 */
        .shadow-sm{ box-shadow:0 1px 2px rgba(13,27,42,.04) !important }
        .shadow, .shadow-md{ box-shadow:0 1px 3px rgba(13,27,42,.06),0 1px 2px rgba(13,27,42,.04) !important }
        /* 카드 라운드 통일 — ce-admin 12px */
        .rounded-2xl{ border-radius:12px !important }
        /* 사이드바 메뉴 글자 키우기 (html 13px 기준이라 text-sm 이 작게 보임) + 줄바꿈 방지 */
        aside nav a, aside nav button { font-size:14.5px !important; white-space:nowrap; }
        aside nav .flex-1 { min-width:0; }   /* 그룹 라벨 영역 축소 허용(캐럿 유지) */
        /* 입력 포커스 링 — 틸 */
        input:focus, select:focus, textarea:focus{ outline:none }
        /* 스크롤바 */
        ::-webkit-scrollbar{width:10px;height:10px}
        ::-webkit-scrollbar-thumb{background:#D0D5DA;border-radius:8px;border:2px solid transparent;background-clip:content-box}
        ::-webkit-scrollbar-thumb:hover{background:#B4BBC2;background-clip:content-box}
        ::-webkit-scrollbar-track{background:transparent}
        /* ===== MDI 워크스페이스 탭 ===== */
        #ws-tabs::-webkit-scrollbar{height:0}
        .ws-tab{display:inline-flex;align-items:center;gap:6px;height:34px;padding:0 8px 0 14px;border-radius:10px 10px 0 0;background:transparent;color:#656C74;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;border:1px solid transparent;border-bottom:none;max-width:220px}
        .ws-tab:hover{background:rgba(255,255,255,.6)}
        .ws-tab.active{background:#fff;color:#101317;border-color:#E8EAEC}
        .ws-tab .t{overflow:hidden;text-overflow:ellipsis}
        .ws-tab-x{width:18px;height:18px;display:grid;place-items:center;border-radius:5px;color:#999EA4;font-size:16px;line-height:1;border:none;background:transparent;cursor:pointer;flex:0 0 auto}
        .ws-tab-x:hover{background:#F3F5F7;color:#D73D3F}
        .ws-frame{position:absolute;inset:0;width:100%;height:100%;border:0;background:#F3F5F7;display:none}
        .ws-frame.active{display:block}
    </style>
    @stack('head')
</head>
@php
    $user = auth()->user();
    $role = $user->role ?: ($user->is_admin ? 'hq' : '');
    $roleLabel = \App\Models\User::ROLES[$role] ?? '사용자';
    // 본사 사이드바 — 승인 대기 중인 회원가입 신청 수 (뱃지)
    $pendingSignups = $role === 'hq' ? \App\Models\User::pendingApproval()->count() : 0;
    // 2레벨 메뉴 정의는 App\Support\PortalMenu 로 이동(환경설정 화면과 공유)
    $menus = \App\Support\PortalMenu::menus();
    $nav = $menus[$role] ?? $menus['hq'];
    // 출근관리 — 아르바이트는 출근관리만, 정직원은 기존 메뉴 + 출근 승인/급여
    if ($user->isPartTime()) {
        $nav = [
            ['출근관리', '🕐', [
                ['portal.attendance.index', '출퇴근 관리', []],
                ['portal.leaves.index', '휴무 관리', []],
            ]],
        ];
    } else {
        $nav[] = ['출근관리', '🕐', [
            ['portal.attendance.approvals', '출근 관리', []],
            ['portal.wages.index', '급여 관리', []],
        ]];
    }
    // 환경설정에서 숨긴 메뉴 제외 (자식이 모두 숨겨진 그룹은 그룹째 제거)
    $hiddenMenus = \App\Models\MenuSetting::hiddenRoutes();
    if (! empty($hiddenMenus)) {
        $nav = collect($nav)->map(function ($group) use ($hiddenMenus) {
            [$g, $icon, $children] = $group;
            $children = array_values(array_filter($children, fn ($c) => ! in_array($c[0], $hiddenMenus, true)));
            return [$g, $icon, $children];
        })->filter(fn ($group) => count($group[2]) > 0)->values()->all();
    }
    $isChildActive = function ($child) {
        [$r, , $also] = $child;
        return request()->routeIs($r) || collect($also)->contains(fn ($x) => request()->routeIs($x));
    };
    $badge = ['hq' => 'bg-mango-500', 'store' => 'bg-emerald-500', 'supplier' => 'bg-sky-500'][$role] ?? 'bg-neutral-500';
@endphp
<body class="font-sans text-neutral-800">
@php $panelMode = request()->boolean('panel'); @endphp
@if ($panelMode)
    {{-- 패널(옆 탭) 모드 — 사이드바·헤더 없이 상세 내용만. 리스트 화면이 이 #panel-root 를
         AJAX 로 가져와 상세보기 탭에 직접 임베드한다(iframe 아님). --}}
    <div id="panel-root" class="p-3 lg:p-4">
        @if (session('success'))
            <div class="mb-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-2.5 text-sm font-medium">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-2.5 text-sm font-medium">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-2.5 text-sm"><ul class="list-disc list-inside space-y-1">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif
        @yield('content')
        {{-- 뷰가 push 한 스크립트도 #panel-root 안에 두어 AJAX 임베드 시 함께 실행되게 함 --}}
        @stack('scripts')
    </div>
@else
<div class="flex min-h-screen">
    {{-- Sidebar — ce-admin 화이트 플로팅 패널 --}}
    <aside class="hidden lg:flex w-72 shrink-0 sticky top-0 h-screen p-2.5">
      <div class="flex flex-col w-full bg-white rounded-xl border border-neutral-200 overflow-hidden">
        <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-2 h-14 px-4 border-b border-neutral-100">
            <span class="text-2xl">🥭</span>
            <span class="font-black text-neutral-900"><span class="text-mango-500">LEE</span>FRIENDS</span>
        </a>
        <div class="px-4 py-3 border-b border-neutral-100">
            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-white {{ $badge }} px-2.5 py-1 rounded-full">{{ $roleLabel }} 포털</span>
            @if ($role === 'store' && $user->store)
                <p class="text-sm text-neutral-700 mt-2 font-semibold">{{ $user->store->name }}</p>
            @elseif ($role === 'supplier' && $user->supplier)
                <p class="text-sm text-neutral-700 mt-2 font-semibold">{{ $user->supplier->name }}</p>
            @endif
        </div>
        <nav class="flex-1 min-h-0 p-2.5 space-y-0.5 overflow-y-auto">
            @foreach ($nav as [$groupLabel, $groupIcon, $children])
                @php $groupActive = collect($children)->contains(fn ($c) => $isChildActive($c)); @endphp
                @if (count($children) === 1 && empty($children[0][2]) && in_array($children[0][0], ['portal.dashboard', 'portal.chat.index', 'portal.hq.notices.index', 'portal.notices.index', 'portal.staff.index', 'portal.schedules.index'], true))
                    {{-- 단일 링크 그룹 (대시보드) --}}
                    @php [$r, $label, $also] = $children[0]; $active = $isChildActive($children[0]); @endphp
                    <a href="{{ route($r) }}"
                       class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-semibold transition {{ $active ? 'bg-mango-50 text-mango-600 font-bold' : 'text-neutral-700 hover:bg-neutral-50 hover:text-mango-600' }}">
                        <span>{{ $groupIcon }}</span> {{ $label }}
                    </a>
                @else
                    <div x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open"
                                class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-bold transition {{ $groupActive ? 'text-mango-600' : 'text-neutral-600 hover:text-mango-600 hover:bg-neutral-50' }}">
                            <span>{{ $groupIcon }}</span>
                            <span class="flex-1 text-left">{{ $groupLabel }}</span>
                            <svg class="w-4 h-4 transition-transform text-neutral-400" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div x-show="open" x-collapse class="mt-0.5 ml-3 pl-3 border-l border-neutral-200 space-y-0.5">
                            @foreach ($children as $child)
                                @php [$r, $label, $also] = $child; $active = $isChildActive($child); @endphp
                                <a href="{{ route($r) }}"
                                   class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-semibold transition {{ $active ? 'bg-mango-50 text-mango-600 font-bold' : 'text-neutral-600 hover:bg-neutral-50 hover:text-mango-600' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $active ? 'bg-mango-500' : 'bg-neutral-300' }}"></span>
                                    {{ $label }}
                                    @if ($r === 'portal.hq.registrations.index' && $pendingSignups > 0)
                                        <span class="ml-auto inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-rose-500 text-white text-[11px] font-bold">{{ $pendingSignups }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>
        <div class="p-3 border-t border-neutral-100">
            <a href="{{ route('home') }}" target="_blank" class="block px-3 py-1.5 text-sm text-neutral-500 hover:text-neutral-900">홈페이지 ↗</a>
            <form method="POST" action="{{ route('portal.logout') }}">@csrf
                <button class="w-full text-left px-3 py-1.5 text-sm text-rose-500 hover:text-rose-600">로그아웃</button>
            </form>
        </div>
      </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-transparent flex items-center justify-between px-4 lg:px-4 sticky top-0 z-30">
            <div class="min-w-0">
                <h1 class="text-lg font-extrabold text-neutral-900 leading-none truncate">@yield('title', '포털')</h1>
            </div>
            <div class="flex items-center gap-2 text-sm">
                @if (in_array($role, ['hq', 'store', 'supplier'], true))
                    @php $todaySchedules = \App\Models\Schedule::forUser($user)->onDate(today())->orderBy('id')->get(); @endphp
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="relative w-9 h-9 grid place-items-center rounded-lg bg-white border border-neutral-200 text-neutral-700 hover:bg-mango-50 hover:border-mango-200 hover:text-mango-600 transition" title="오늘 일정">
                            <span class="text-xl">📅</span>
                            @if ($todaySchedules->count() > 0)
                                <span class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 grid place-items-center text-[10px] font-bold text-white bg-mango-500 rounded-full">{{ $todaySchedules->count() }}</span>
                            @endif
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top.right
                             class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-neutral-100 overflow-hidden z-50">
                            <div class="px-4 py-3 border-b border-neutral-100 font-extrabold text-neutral-900">오늘 일정 <span class="text-neutral-400 font-normal">{{ today()->format('m월 d일') }}</span></div>
                            <div class="max-h-96 overflow-y-auto divide-y divide-neutral-50">
                                @forelse ($todaySchedules as $s)
                                    <div class="px-4 py-3">
                                        <p class="text-sm font-bold text-neutral-800">{{ $s->title }}</p>
                                        @if ($s->content)<p class="text-xs text-neutral-500 mt-0.5 whitespace-pre-line">{{ \Illuminate\Support\Str::limit($s->content, 120) }}</p>@endif
                                    </div>
                                @empty
                                    <p class="px-4 py-8 text-center text-sm text-neutral-400">오늘 등록된 일정이 없습니다.</p>
                                @endforelse
                            </div>
                            <a href="{{ route('portal.schedules.index') }}" class="block text-center py-2.5 text-sm font-bold text-neutral-500 hover:bg-neutral-50 border-t border-neutral-100">일정 관리</a>
                        </div>
                    </div>
                @endif
                @php
                    $recentNotis = $user->notifications()->take(6)->get();
                    $unreadCount = $user->notifications()->whereNull('read_at')->count();
                @endphp
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="relative w-9 h-9 grid place-items-center rounded-lg bg-white border border-neutral-200 text-neutral-700 hover:bg-mango-50 hover:border-mango-200 hover:text-mango-600 transition">
                        <span class="text-xl">🔔</span>
                        <span id="noti-badge" data-count="{{ $unreadCount }}"
                              class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 grid place-items-center text-[10px] font-bold text-white bg-rose-500 rounded-full {{ $unreadCount > 0 ? '' : 'hidden' }}">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" x-transition.origin.top.right
                         class="absolute right-0 mt-2 w-80 bg-white rounded-2xl shadow-xl border border-neutral-100 overflow-hidden z-50">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-neutral-100">
                            <span class="font-extrabold text-neutral-900">알림</span>
                            @if ($unreadCount > 0)
                                <form method="POST" action="{{ route('portal.notifications.read_all') }}">@csrf
                                    <button class="text-xs font-bold text-mango-600 hover:text-mango-700">모두 읽음</button>
                                </form>
                            @endif
                        </div>
                        <div class="max-h-96 overflow-y-auto divide-y divide-neutral-50">
                            @forelse ($recentNotis as $n)
                                <div class="px-4 py-3 {{ $n->read_at ? '' : 'bg-mango-50/50' }}">
                                    <p class="text-sm font-bold text-neutral-800">{{ $n->title }}</p>
                                    <p class="text-xs text-neutral-500 mt-0.5">{{ $n->body }}</p>
                                    <p class="text-[11px] text-neutral-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
                                </div>
                            @empty
                                <p class="px-4 py-8 text-center text-sm text-neutral-400">알림이 없습니다.</p>
                            @endforelse
                        </div>
                        <a href="{{ route('portal.notifications.index') }}" class="block text-center py-2.5 text-sm font-bold text-neutral-500 hover:bg-neutral-50 border-t border-neutral-100">전체 보기</a>
                    </div>
                </div>
                <span class="inline-flex items-center gap-1.5 h-9 rounded-lg bg-white border border-neutral-200 px-3 text-sm font-semibold text-neutral-700">
                    <span class="w-6 h-6 rounded-full bg-mango-500 text-white grid place-items-center text-[11px] font-bold">{{ mb_substr($user->name, 0, 1) }}</span>
                    {{ $user->name }} <span class="text-neutral-400 font-normal">({{ $roleLabel }})</span>
                </span>
                <form method="POST" action="{{ route('portal.logout') }}">@csrf
                    <button class="h-9 rounded-lg bg-white border border-neutral-200 hover:bg-neutral-50 px-3 text-sm font-semibold text-neutral-600">로그아웃</button>
                </form>
            </div>
        </header>

        {{-- MDI 워크스페이스 — 메뉴를 누르면 화면이 상단 탭(iframe)으로 열린다. 실제 화면은 ?panel=1 모드로 iframe 안에 렌더됨. --}}
        <div id="ws" class="flex-1 flex flex-col min-w-0 overflow-hidden"
             data-home-url="{{ route('portal.dashboard') }}" data-home-title="대시보드">
            <div id="ws-tabs" class="shrink-0 flex items-stretch gap-1 px-2.5 pt-1.5 overflow-x-auto"></div>
            <div id="ws-frames" class="flex-1 min-h-0 relative"></div>
        </div>
    </div>
</div>
@endif
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

{{-- MDI 워크스페이스: 메뉴 클릭 → 화면이 상단 탭(iframe, ?panel=1)으로 열림 --}}
<script>
(function () {
    if (window.self !== window.top) return;               // 탭(iframe) 내부에서는 실행 안 함
    const ws = document.getElementById('ws');
    const tabsEl = document.getElementById('ws-tabs');
    const framesEl = document.getElementById('ws-frames');
    if (!ws || !tabsEl || !framesEl) return;

    const KEY = 'ww_ws_tabs';
    const homeUrl = ws.dataset.homeUrl;
    let seq = 0, tabs = [], activeId = null;

    const parse = (u) => { const a = document.createElement('a'); a.href = u; return a; };
    const norm = (u) => { const a = parse(u); return a.pathname + a.search; };
    const stripPanel = (u) => { const a = parse(u); const p = new URLSearchParams(a.search); p.delete('panel'); const q = p.toString(); return a.pathname + (q ? '?' + q : ''); };
    const withPanel = (u) => { const a = parse(u); const p = new URLSearchParams(a.search); p.set('panel', '1'); return a.pathname + '?' + p.toString(); };
    const cleanTitle = (t) => (t || '').replace(/\s*·\s*LEEFRIENDS.*$/, '').replace(/\s*·\s*포털.*$/, '').trim() || '화면';
    const isPortal = (path) => path.indexOf('/portal') !== -1 && path.indexOf('/portal/login') === -1;
    // 메뉴 링크 텍스트에서 선두 아이콘(이모지)·후미 배지 숫자를 떼어 라벨만
    const menuLabel = (a) => {
        let s = (a.textContent || '').replace(/\s+/g, ' ').trim();
        try { s = s.replace(/^[^\p{L}\p{N}]+/u, ''); } catch (e) { s = s.replace(/^[^가-힣A-Za-z0-9]+/, ''); }
        return s.replace(/\s+\d+$/, '').trim() || '화면';
    };
    // 경로가 사이드바 메뉴와 일치하면 그 메뉴 라벨을 반환(탭 이름을 메뉴명과 일치시키기 위함)
    const menuTitleForPath = (path) => {
        const links = document.querySelectorAll('aside a[href]');
        for (let i = 0; i < links.length; i++) { if (norm(links[i].getAttribute('href')) === path) return menuLabel(links[i]); }
        return null;
    };

    function persist() {
        try { sessionStorage.setItem(KEY, JSON.stringify({ tabs: tabs.map(t => ({ url: t.url, title: t.title })), active: (tabs.find(t => t.id === activeId) || {}).url })); } catch (e) {}
    }
    function syncHeader(t) { const h = document.querySelector('header h1'); if (h && t) h.textContent = t.title; }

    function renderTabs() {
        tabsEl.innerHTML = '';
        tabs.forEach((t) => {
            const el = document.createElement('div');
            el.className = 'ws-tab' + (t.id === activeId ? ' active' : '');
            const s = document.createElement('span'); s.className = 't'; s.textContent = t.title; el.appendChild(s);
            const x = document.createElement('button'); x.type = 'button'; x.className = 'ws-tab-x'; x.innerHTML = '&times;'; x.title = '탭 닫기';
            x.addEventListener('click', (e) => { e.stopPropagation(); closeTab(t.id); });
            el.appendChild(x);
            el.addEventListener('click', () => activate(t.id));
            el.addEventListener('auxclick', (e) => { if (e.button === 1) { e.preventDefault(); closeTab(t.id); } });
            tabsEl.appendChild(el);
        });
    }

    function activate(id) {
        const t = tabs.find(t => t.id === id); if (!t) return;
        activeId = id;
        if (!t.frame) {
            t.frame = document.createElement('iframe');
            t.frame.className = 'ws-frame';
            t.frame.src = withPanel(t.url);
            t.frame.addEventListener('load', () => onFrameLoad(t));
            framesEl.appendChild(t.frame);
        }
        tabs.forEach(o => { if (o.frame) o.frame.classList.toggle('active', o.id === id); });
        renderTabs(); persist(); syncHeader(t);
    }

    function onFrameLoad(t) {
        try {
            const doc = t.frame.contentDocument; if (!doc) return;
            const loc = doc.location.pathname + doc.location.search;
            if (loc.indexOf('/portal/login') !== -1) { window.top.location.href = stripPanel(loc); return; }  // 세션만료 → 상단창 로그인
            // iframe 이 전체 셸(사이드바+워크스페이스)을 로드했으면 중첩이므로 panel 모드로 다시 로드
            if (doc.getElementById('ws') && !new URLSearchParams(doc.location.search).has('panel')) {
                t.frame.src = withPanel(stripPanel(loc));
                return;
            }
            const cur = stripPanel(loc); if (cur) t.url = cur;
            if (!t.titled) { const ti = cleanTitle(doc.title); if (ti) t.title = ti; }   // 메뉴 라벨 탭은 제목 유지
            renderTabs(); persist(); if (t.id === activeId) syncHeader(t);
        } catch (e) {}
    }

    function openTab(url, title) {
        const path = norm(stripPanel(url));
        const menuT = menuTitleForPath(path);   // 메뉴와 일치하면 메뉴 라벨을 우선
        if (menuT) title = menuT;
        const ex = tabs.find(t => norm(t.url) === path);
        if (ex) {
            if (title) { ex.title = title; ex.titled = true; }   // 메뉴 클릭이면 탭 이름을 메뉴 라벨로 고정
            activate(ex.id);
            return;
        }
        const t = { id: ++seq, url: stripPanel(url), title: title || '화면', titled: !!title, frame: null };
        tabs.push(t); activate(t.id);
    }
    window.ceOpenTab = openTab;

    function closeTab(id) {
        const idx = tabs.findIndex(t => t.id === id); if (idx < 0) return;
        if (tabs[idx].frame) tabs[idx].frame.remove();
        tabs.splice(idx, 1);
        if (activeId === id) {
            const next = tabs[idx] || tabs[idx - 1];
            if (next) activate(next.id); else openTab(homeUrl, '대시보드');
        } else { renderTabs(); persist(); }
    }

    // 사이드바 메뉴 클릭 → 탭으로 열기
    const aside = document.querySelector('aside');
    if (aside) aside.addEventListener('click', (e) => {
        const a = e.target.closest('a[href]'); if (!a) return;
        if (a.target === '_blank' || e.metaKey || e.ctrlKey || e.shiftKey) return;
        const href = a.getAttribute('href'); if (!href || href.charAt(0) === '#') return;
        const path = norm(href); if (!isPortal(path)) return;
        e.preventDefault();
        openTab(href, menuLabel(a));
    });

    // 초기화: 세션 복원 + 현재 URL 탭
    let saved = null; try { saved = JSON.parse(sessionStorage.getItem(KEY) || 'null'); } catch (e) {}
    const curUrl = stripPanel(location.pathname + location.search);
    const curTitle = cleanTitle(document.title);
    if (saved && Array.isArray(saved.tabs) && saved.tabs.length) {
        saved.tabs.forEach(s => tabs.push({ id: ++seq, url: s.url, title: s.title || '화면', titled: true, frame: null }));
        let cur = tabs.find(t => norm(t.url) === norm(curUrl));
        if (!cur && isPortal(norm(curUrl))) { cur = { id: ++seq, url: curUrl, title: curTitle, frame: null }; tabs.push(cur); }
        renderTabs();
        activate((cur || tabs.find(t => t.url === saved.active) || tabs[0]).id);
    } else {
        openTab(curUrl, curTitle);
    }
})();
</script>

{{-- 전역 커스텀 확인 다이얼로그 — data-confirm="메시지" 폼에 적용 --}}
<div x-data="confirmDialog()" x-init="init()">
    <div x-show="open" x-cloak class="fixed inset-0 z-[90] grid place-items-center bg-black/40 p-4" @click.self="cancel()" @keydown.escape.window="cancel()">
        <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="px-6 pt-6 pb-2 flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-mango-100 text-mango-600 grid place-items-center text-lg shrink-0">💬</div>
                <p class="text-sm text-neutral-700 font-medium whitespace-pre-line mt-1" x-text="msg"></p>
            </div>
            <div class="flex gap-2 px-6 py-4">
                <button type="button" @click="ok()" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition">확인</button>
                <button type="button" @click="cancel()" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
            </div>
        </div>
    </div>
</div>
<script>
    function confirmDialog() {
        return {
            open: false, msg: '', _form: null,
            init() {
                document.addEventListener('submit', (e) => {
                    const f = e.target;
                    if (f && f.dataset && f.dataset.confirm && !f.dataset.confirmed) {
                        e.preventDefault();
                        this.msg = f.dataset.confirm.replace(/\\n/g, '\n');
                        this._form = f;
                        this.open = true;
                    }
                }, true);
            },
            ok() { const f = this._form; this.open = false; if (f) { f.dataset.confirmed = '1'; f.submit(); } },
            cancel() { this.open = false; this._form = null; },
        };
    }
</script>

{{-- 화면 탭(iframe) 내부: 폼·링크가 panel=1 을 유지해 전체 셸이 중첩 로드되는 것을 방지 --}}
<script>
(function () {
    if (window.self === window.top) return;   // 탭(iframe) 안에서만
    const parseA = (u) => { const a = document.createElement('a'); a.href = u; return a; };
    const hasPanel = (u) => new URLSearchParams(parseA(u).search).has('panel');
    const withPanel = (u) => { const a = parseA(u); const p = new URLSearchParams(a.search); p.set('panel', '1'); return a.pathname + '?' + p.toString(); };
    const isInternal = (a) => { try { const u = new URL(a.href); return u.origin === location.origin && u.pathname.indexOf('/portal') !== -1; } catch (e) { return false; } };
    const addPanelInput = (form) => { if (form && !form.querySelector('input[name="panel"]')) { const i = document.createElement('input'); i.type = 'hidden'; i.name = 'panel'; i.value = '1'; form.appendChild(i); } };
    const initForms = () => document.querySelectorAll('form').forEach(addPanelInput);
    if (document.readyState !== 'loading') initForms(); else document.addEventListener('DOMContentLoaded', initForms);
    document.addEventListener('submit', (e) => { if (e.target instanceof HTMLFormElement) addPanelInput(e.target); }, true);
    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href]'); if (!a) return;
        if ((a.target && a.target !== '_self') || a.hasAttribute('download')) return;
        const href = a.getAttribute('href'); if (!href || href.charAt(0) === '#' || /^(javascript:|mailto:|tel:)/i.test(href)) return;
        if (!isInternal(a) || hasPanel(a.href)) return;
        a.setAttribute('href', withPanel(a.href));
    }, true);
})();
</script>

{{-- 실시간 토스트 알림 (Pusher) --}}
<div id="toast-container" class="fixed bottom-5 right-5 z-[100] flex flex-col gap-2 w-80 max-w-[90vw] pointer-events-none"></div>
@if (config('broadcasting.connections.pusher.key') && auth()->id())
<script src="https://js.pusher.com/8.4/pusher.min.js"></script>
<script>
(function () {
    if (window.self !== window.top) return;   // 워크스페이스 탭(iframe)에서는 상단창만 실시간 연결
    const KEY = @json(config('broadcasting.connections.pusher.key'));
    const CLUSTER = @json(config('broadcasting.connections.pusher.options.cluster'));
    const USER_ID = @json(auth()->id());
    const csrf = document.querySelector('meta[name=csrf-token]')?.content;

    let pusher;
    try {
        pusher = new Pusher(KEY, {
            cluster: CLUSTER,
            forceTLS: true,
            authEndpoint: @json(url('/broadcasting/auth')),
            auth: { headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' } },
        });
    } catch (e) { console.error('Pusher init 실패', e); return; }

    // 다른 페이지 스크립트(채팅 등)에서 동일 연결 재사용
    window.appPusher = pusher;

    const channel = pusher.subscribe('private-portal.user.' + USER_ID);
    channel.bind('app.notification', function (data) {
        showToast(data && data.title || '알림', data && data.body || '');
        if (data && data.id) bumpBadge(); // 영구 알림(id 있음)만 벨 카운트 증가, 채팅 토스트는 제외
    });

    function bumpBadge() {
        const b = document.getElementById('noti-badge');
        if (!b) return;
        const n = (parseInt(b.dataset.count || '0', 10) || 0) + 1;
        b.dataset.count = n;
        b.textContent = n > 99 ? '99+' : n;
        b.classList.remove('hidden');
    }

    function showToast(title, body) {
        const c = document.getElementById('toast-container');
        if (!c) return;
        const el = document.createElement('div');
        el.className = 'pointer-events-auto rounded-xl bg-white shadow-lg border border-neutral-200 px-4 py-3 ' +
                       'flex items-start gap-3 translate-x-6 opacity-0 transition-all duration-300';
        el.innerHTML =
            '<span class="text-xl shrink-0">🔔</span>' +
            '<div class="min-w-0 flex-1">' +
              '<p class="js-t text-sm font-bold text-neutral-900"></p>' +
              '<p class="js-b text-xs text-neutral-500 mt-0.5 break-words"></p>' +
            '</div>' +
            '<button class="js-x text-neutral-300 hover:text-neutral-500 shrink-0 leading-none" aria-label="닫기">✕</button>';
        el.querySelector('.js-t').textContent = title;
        el.querySelector('.js-b').textContent = body;
        c.appendChild(el);
        requestAnimationFrame(() => el.classList.remove('translate-x-6', 'opacity-0'));
        const timer = setTimeout(remove, 6000);
        el.querySelector('.js-x').onclick = remove;
        function remove() {
            clearTimeout(timer);
            el.classList.add('translate-x-6', 'opacity-0');
            setTimeout(() => el.remove(), 300);
        }
    }
})();
</script>
@endif
<script>
    // 모달 등에서 새 탭 없이 페이지 내 숨김 iframe으로 인쇄 (url 은 ?print=1 자동인쇄 페이지)
    window.printFrame = function (url) {
        document.querySelectorAll('iframe.__print-frame').forEach(el => el.remove());
        const f = document.createElement('iframe');
        f.className = '__print-frame';
        f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
        f.src = url;
        f.onload = () => {
            try { f.contentWindow.addEventListener('afterprint', () => setTimeout(() => f.remove(), 300)); } catch (e) {}
            setTimeout(() => { if (document.body.contains(f)) f.remove(); }, 120000);
        };
        document.body.appendChild(f);
    };
</script>
@unless ($panelMode)
    @stack('scripts')
@endunless
</body>
</html>
