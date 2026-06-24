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
            colors: { mango: { 50:'#FFF9ED',100:'#FFF1D2',400:'#FFB23D',500:'#FF9F1C',600:'#F2784B',700:'#D45A1F' } },
        }}}
    </script>
    <style>[x-cloak]{display:none!important}</style>
</head>
@php
    $user = auth()->user();
    $role = $user->role ?: ($user->is_admin ? 'hq' : '');
    $roleLabel = \App\Models\User::ROLES[$role] ?? '사용자';
    // 2레벨 메뉴: 각 그룹 = [그룹명, 아이콘, 자식[]]. 자식 = [route, label, also[]]. 자식이 비면 그룹 자체가 링크.
    $menus = [
        'hq' => [
            ['대시보드', '📊', [['portal.dashboard', '대시보드', []]]],
            ['채팅', '💬', [['portal.chat.index', '채팅', []]]],
            ['주문 · 판매', '📦', [
                ['portal.hq.orders.index', '발주(구매주문)', ['portal.hq.orders.show']],
                ['portal.hq.sales_orders.index', '판매주문', ['portal.hq.sales_orders.show']],
                ['portal.hq.supplier_orders.index', '공급사 발주 현황', []],
                ['portal.order_changes.index', '매장 주문 변경', []],
            ]],
            ['출고 · 배송', '🚚', [
                ['portal.hq.shipments.index', '출고 관리', ['portal.hq.shipments.create','portal.hq.shipments.show']],
            ]],
            ['정산 · 현황', '📈', [
                ['portal.hq.sales', '매출 현황', []],
                ['portal.hq.statements.create', '거래명세서 작성', []],
                ['portal.hq.statements.index', '거래명세서 이력', []],
                ['portal.hq.tax_invoices.index', '세금계산서(발행)', []],
                ['portal.hq.invoices.index', '세금계산서(수취)', []],
            ]],
            ['기준정보', '🗂️', [
                ['portal.hq.products.index', '품목 관리', []],
                ['portal.hq.categories.index', '카테고리 관리', []],
                ['portal.hq.suppliers.index', '공급처 관리', []],
                ['portal.hq.stores.index', '매장 관리', []],
            ]],
            ['공지사항', '📢', [['portal.hq.notices.index', '공지사항', []]]],
            ['창업 문의', '📨', [
                ['portal.hq.inquiries.index', '창업 문의', ['portal.hq.inquiries.show']],
            ]],
        ],
        'store' => [
            ['대시보드', '📊', [['portal.dashboard', '대시보드', []]]],
            ['채팅', '💬', [['portal.chat.index', '본사 채팅', []]]],
            ['공지사항', '📢', [['portal.notices.index', '공지사항', []]]],
            ['발주', '🛒', [
                ['portal.store.orders.create', '재료 발주하기', []],
                ['portal.store.orders.index', '발주 내역', ['portal.store.orders.show','portal.store.orders.edit']],
                ['portal.store.sample_orders.create', '샘플 주문하기', []],
                ['portal.store.sample_orders.index', '샘플 주문 내역', []],
            ]],
            ['입고 · 재고', '📦', [
                ['portal.store.inbound', '입고예정 · 배송', ['portal.store.shipments.show']],
                ['portal.store.inventory.index', '재고 관리', ['portal.store.inventory.movements']],
            ]],
            ['현황', '📈', [
                ['portal.store.purchases', '구매 현황', []],
            ]],
        ],
        'supplier' => [
            ['대시보드', '📊', [['portal.dashboard', '대시보드', []]]],
            ['채팅', '💬', [['portal.chat.index', '본사 채팅', []]]],
            ['공지사항', '📢', [['portal.notices.index', '공지사항', []]]],
            ['물품', '🗂️', [
                ['portal.supplier.products.index', '물품 관리', []],
            ]],
            ['주문 · 판매', '📦', [
                ['portal.supplier.orders.index', '주문 관리', ['portal.supplier.orders.show']],
                ['portal.supplier.sales_orders.index', '판매주문', ['portal.supplier.sales_orders.show']],
                ['portal.order_changes.index', '매장 주문 변경', []],
            ]],
            ['출고 · 배송', '🚚', [
                ['portal.supplier.shipments.index', '출고 관리', ['portal.supplier.shipments.create','portal.supplier.shipments.show']],
            ]],
            ['정산 · 현황', '📈', [
                ['portal.supplier.sales', '매출 현황', []],
                ['portal.supplier.invoices.index', '세금계산서 발행', ['portal.supplier.invoices.create','portal.supplier.invoices.show']],
            ]],
        ],
    ];
    $nav = $menus[$role] ?? $menus['hq'];
    $isChildActive = function ($child) {
        [$r, , $also] = $child;
        return request()->routeIs($r) || collect($also)->contains(fn ($x) => request()->routeIs($x));
    };
    $badge = ['hq' => 'bg-mango-500', 'store' => 'bg-emerald-500', 'supplier' => 'bg-sky-500'][$role] ?? 'bg-neutral-500';
@endphp
<body class="font-sans bg-neutral-100 text-neutral-800">
<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="hidden lg:flex w-64 shrink-0 flex-col bg-neutral-900 text-neutral-300">
        <a href="{{ route('portal.dashboard') }}" class="flex items-center gap-2 h-16 px-6 border-b border-white/10">
            <span class="text-2xl">🥭</span>
            <span class="font-black text-white"><span class="text-mango-400">LEE</span>FRIENDS</span>
        </a>
        <div class="px-6 py-4 border-b border-white/10">
            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-white {{ $badge }} px-2.5 py-1 rounded-full">{{ $roleLabel }} 포털</span>
            @if ($role === 'store' && $user->store)
                <p class="text-sm text-white/80 mt-2 font-semibold">{{ $user->store->name }}</p>
            @elseif ($role === 'supplier' && $user->supplier)
                <p class="text-sm text-white/80 mt-2 font-semibold">{{ $user->supplier->name }}</p>
            @endif
        </div>
        <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
            @foreach ($nav as [$groupLabel, $groupIcon, $children])
                @php $groupActive = collect($children)->contains(fn ($c) => $isChildActive($c)); @endphp
                @if (count($children) === 1 && empty($children[0][2]) && in_array($children[0][0], ['portal.dashboard', 'portal.chat.index', 'portal.hq.notices.index', 'portal.notices.index'], true))
                    {{-- 단일 링크 그룹 (대시보드) --}}
                    @php [$r, $label, $also] = $children[0]; $active = $isChildActive($children[0]); @endphp
                    <a href="{{ route($r) }}"
                       class="flex items-center gap-3 px-4 py-2.5 rounded-xl font-semibold transition {{ $active ? 'bg-mango-500 text-white' : 'hover:bg-white/5 hover:text-white' }}">
                        <span>{{ $groupIcon }}</span> {{ $label }}
                    </a>
                @else
                    <div x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open"
                                class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl font-bold transition {{ $groupActive ? 'text-white' : 'text-neutral-400 hover:text-white hover:bg-white/5' }}">
                            <span>{{ $groupIcon }}</span>
                            <span class="flex-1 text-left">{{ $groupLabel }}</span>
                            <svg class="w-4 h-4 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 9l6 6 6-6"/></svg>
                        </button>
                        <div x-show="open" x-collapse class="mt-1 ml-3 pl-3 border-l border-white/10 space-y-0.5">
                            @foreach ($children as $child)
                                @php [$r, $label, $also] = $child; $active = $isChildActive($child); @endphp
                                <a href="{{ route($r) }}"
                                   class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold transition {{ $active ? 'bg-mango-500 text-white' : 'text-neutral-300 hover:bg-white/5 hover:text-white' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $active ? 'bg-white' : 'bg-white/30' }}"></span>
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </nav>
        <div class="p-4 border-t border-white/10">
            <a href="{{ route('home') }}" target="_blank" class="block px-4 py-2 text-sm hover:text-white">홈페이지 ↗</a>
            <form method="POST" action="{{ route('portal.logout') }}">@csrf
                <button class="w-full text-left px-4 py-2 text-sm text-rose-400 hover:text-rose-300">로그아웃</button>
            </form>
        </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-neutral-200 flex items-center justify-between px-5 lg:px-8 sticky top-0 z-30">
            <div class="min-w-0">
                <p class="text-[11px] font-bold text-neutral-400 leading-none mb-1">{{ $roleLabel }} 포털 <span class="text-neutral-300">›</span> @yield('title', '포털')</p>
                <h1 class="text-lg font-extrabold text-neutral-900 leading-none truncate">@yield('title', '포털')</h1>
            </div>
            <div class="flex items-center gap-3 text-sm">
                @php
                    $recentNotis = $user->notifications()->take(6)->get();
                    $unreadCount = $user->notifications()->whereNull('read_at')->count();
                @endphp
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="relative w-10 h-10 grid place-items-center rounded-xl hover:bg-neutral-100">
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
                <span class="text-neutral-500">{{ $user->name }} ({{ $roleLabel }})</span>
                <form method="POST" action="{{ route('portal.logout') }}">@csrf
                    <button class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1.5 font-semibold">로그아웃</button>
                </form>
            </div>
        </header>

        <main class="flex-1 p-5 lg:p-8">
            @if (in_array($role, ['hq', 'supplier'], true))
                @php
                    [$ocType, $ocSid] = \App\Http\Controllers\Portal\OrderChangeController::sellerContext($user);
                    $pendingChanges = \App\Models\OrderChange::forSeller($ocType, $ocSid)->pending()->latest()->get();
                @endphp
                @if ($pendingChanges->isNotEmpty())
                    <div class="mb-5 rounded-2xl bg-amber-50 border-2 border-amber-300 px-5 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <span class="text-2xl">⚠️</span>
                                <div>
                                    <p class="font-extrabold text-amber-900">매장 주문 변경 {{ $pendingChanges->count() }}건 미반영</p>
                                    <ul class="mt-1 text-sm text-amber-800 space-y-0.5">
                                        @foreach ($pendingChanges->take(3) as $pc)
                                            <li>· [{{ $pc->type_label }}] {{ $pc->summary }}</li>
                                        @endforeach
                                        @if ($pendingChanges->count() > 3)
                                            <li class="text-amber-600">외 {{ $pendingChanges->count() - 3 }}건…</li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2 shrink-0">
                                <a href="{{ route('portal.order_changes.index') }}" class="text-center rounded-lg bg-white border border-amber-300 text-amber-800 font-bold px-4 py-2 text-sm hover:bg-amber-100">확인하기</a>
                                <form method="POST" action="{{ route('portal.order_changes.ack_all') }}" onsubmit="return confirm('모든 변경을 확인(반영) 처리할까요?')">
                                    @csrf
                                    <button class="w-full rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold px-4 py-2 text-sm">모두 반영</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

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
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

{{-- 실시간 토스트 알림 (Pusher) --}}
<div id="toast-container" class="fixed bottom-5 right-5 z-[100] flex flex-col gap-2 w-80 max-w-[90vw] pointer-events-none"></div>
@if (config('broadcasting.connections.pusher.key') && auth()->id())
<script src="https://js.pusher.com/8.4/pusher.min.js"></script>
<script>
(function () {
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
@stack('scripts')
</body>
</html>
