@extends('portal.layout')
@section('title', '홈')

@php
    use Illuminate\Support\Carbon;
    $store = $user->store;
    $today = Carbon::now();
    $dow = ['일', '월', '화', '수', '목', '금', '토'][$today->dayOfWeek];
    $unread = $user->notifications()->whereNull('read_at')->count();
    $recentNotis = $user->notifications()->take(4)->get();
    $maxAmt = max(1, collect($weekly)->max('amount'));
    $weekTotalAmt = collect($weekly)->sum('amount');
    $weekTotalCnt = collect($weekly)->sum('count');

    // 빠른 실행 — 라인 아이콘 타일 (배민 '빠른 메뉴' 대응, 자체 SVG)
    $quick = [
        ['재료 발주', route('portal.store.orders.create'), 'accent', '<path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6L5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/>'],
        ['입고 확인', route('portal.store.inbound'), 'info', '<path d="M3 6.5A1.5 1.5 0 0 1 4.5 5H13a1 1 0 0 1 1 1v9H3z"/><path d="M14 9h3.6a1 1 0 0 1 .8.4L21 13v2h-7z"/><circle cx="7" cy="18" r="1.7"/><circle cx="17.5" cy="18" r="1.7"/>'],
        ['재고 관리', route('portal.store.inventory.index'), 'success', '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/>'],
        ['거래명세서', route('portal.store.statements.index'), 'warn', '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/>'],
        ['세금계산서', route('portal.store.tax_invoices.index'), 'default', '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 3v18M8 8h12M8 13h12"/>'],
        ['발주 내역', route('portal.store.orders.index'), 'accent', '<path d="M4 6h16M4 12h16M4 18h10"/>'],
    ];
    $tileGrad = [
        'accent' => 'from-mango-400 to-mango-600', 'info' => 'from-sky-400 to-indigo-500',
        'success' => 'from-emerald-400 to-teal-600', 'warn' => 'from-amber-400 to-orange-500',
        'default' => 'from-slate-400 to-slate-600',
    ];
@endphp

@section('content')
@include('portal.partials.new-products-banner', ['newProducts' => $newProducts, 'href' => route('portal.store.orders.create')])

{{-- ===== 상단: 인사·요약 카드  |  공지·알림 카드 (배민 홈 1열 대응) ===== --}}
<div class="grid lg:grid-cols-3 gap-4 mb-4">
    {{-- 인사 + 이번 달 실적 --}}
    <div class="lg:col-span-2 rounded-2xl bg-white border border-neutral-100 shadow-sm p-6">
        <p class="text-sm text-neutral-400 font-medium">{{ $today->format('Y. n. j') }} ({{ $dow }})</p>
        <h2 class="text-xl font-black text-neutral-900 mt-1">
            {{ $user->name }} 님, 오늘도 좋은 하루 되세요!
        </h2>
        @if ($store)<p class="text-sm text-neutral-500 mt-0.5">{{ $store->name }}</p>@endif

        <a href="{{ route('portal.store.purchases') }}" data-ws-tab
           class="mt-4 flex items-center justify-between rounded-xl bg-mango-50 border border-mango-100 px-5 py-4 hover:bg-mango-100/70 transition group">
            <span class="text-sm font-bold text-mango-700">이번 달 발주 금액</span>
            <span class="text-2xl font-black text-mango-700 group-hover:translate-x-0.5 transition">
                {{ number_format($stats['month_amount']) }}<span class="text-base font-bold">원</span>
                <span class="text-mango-400 ml-1">›</span>
            </span>
        </a>

        <div class="grid grid-cols-2 gap-3 mt-3">
            <a href="{{ route('portal.store.orders.index') }}" data-ws-tab
               class="rounded-xl border border-neutral-100 bg-neutral-50/60 px-5 py-4 hover:border-mango-200 hover:bg-mango-50/40 transition">
                <p class="text-xs text-neutral-500 font-medium">이번 달 발주 건수</p>
                <p class="text-xl font-black text-neutral-900 mt-1">{{ number_format($stats['month_count']) }}<span class="text-sm font-bold text-neutral-400 ml-0.5">건</span></p>
            </a>
            <a href="{{ route('portal.store.orders.index') }}" data-ws-tab
               class="rounded-xl border border-neutral-100 bg-neutral-50/60 px-5 py-4 hover:border-mango-200 hover:bg-mango-50/40 transition">
                <p class="text-xs text-neutral-500 font-medium">진행 중 발주</p>
                <p class="text-xl font-black text-neutral-900 mt-1">{{ number_format($stats['orders_active']) }}<span class="text-sm font-bold text-neutral-400 ml-0.5">건</span></p>
            </a>
        </div>
    </div>

    {{-- 공지사항 + 알림 --}}
    <div class="rounded-2xl bg-white border border-neutral-100 shadow-sm overflow-hidden flex flex-col">
        <div class="px-5 pt-4 pb-3 border-b border-neutral-50">
            <a href="{{ route('portal.notices.index') }}" data-ws-tab class="flex items-center gap-1 font-extrabold text-neutral-900 hover:text-mango-600">
                공지사항 <span class="text-neutral-300">›</span>
            </a>
            <div class="mt-2 space-y-2">
                @forelse ($notices as $n)
                    <a href="{{ route('portal.notices.show', $n) }}" data-ws-tab class="flex items-start gap-2 group">
                        @if ($n->is_pinned)<span class="mt-0.5 text-[10px] font-bold text-white bg-rose-500 rounded px-1.5 py-0.5 shrink-0">중요</span>@endif
                        <span class="text-sm text-neutral-700 group-hover:text-mango-600 line-clamp-1 flex-1">{{ $n->title }}</span>
                        <span class="text-[11px] text-neutral-300 shrink-0">{{ optional($n->published_at)->format('n.j') }}</span>
                    </a>
                @empty
                    <p class="text-sm text-neutral-400 py-2">등록된 공지가 없습니다.</p>
                @endforelse
            </div>
        </div>
        <div class="px-5 pt-3 pb-4 flex-1">
            <div class="flex items-center gap-2">
                <span class="font-extrabold text-neutral-900">알림</span>
                @if ($unread > 0)<span class="text-[11px] font-bold text-white bg-mango-500 rounded-full px-2 py-0.5">{{ $unread > 99 ? '99+' : $unread }}</span>@endif
            </div>
            <div class="mt-2 space-y-2">
                @forelse ($recentNotis as $noti)
                    <div class="flex items-start gap-2">
                        <span class="mt-1.5 w-1.5 h-1.5 rounded-full shrink-0 {{ $noti->read_at ? 'bg-neutral-200' : 'bg-mango-500' }}"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-neutral-800 line-clamp-1">{{ $noti->title }}</p>
                            <p class="text-[11px] text-neutral-400">{{ $noti->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-neutral-400 py-2">새 알림이 없습니다.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ===== 빠른 실행 (배민 '빠른 메뉴' 아이콘 타일 대응) ===== --}}
<div class="rounded-2xl bg-white border border-neutral-100 shadow-sm p-5 mb-4">
    <p class="text-sm font-extrabold text-neutral-500 mb-3">빠른 실행</p>
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
        @foreach ($quick as [$label, $href, $variant, $path])
            <a href="{{ $href }}" data-ws-tab
               class="flex flex-col items-center gap-2 rounded-xl py-4 px-2 hover:bg-neutral-50 transition group">
                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br {{ $tileGrad[$variant] }} grid place-items-center text-white shadow-sm group-hover:scale-105 transition">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $path !!}</svg>
                </span>
                <span class="text-xs font-bold text-neutral-700 text-center leading-tight">{{ $label }}</span>
            </a>
        @endforeach
    </div>
</div>

{{-- ===== 주간 발주 추이 차트  |  매장 지표(우리가게NOW 대응) ===== --}}
<div class="grid lg:grid-cols-3 gap-4 mb-4">
    {{-- 최근 7일 발주 추이 --}}
    <div class="lg:col-span-2 rounded-2xl bg-white border border-neutral-100 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <a href="{{ route('portal.store.orders.index') }}" data-ws-tab class="flex items-center gap-1 font-extrabold text-neutral-900 hover:text-mango-600">
                발주 금액·건수 <span class="text-neutral-300">›</span>
            </a>
            <span class="text-xs text-neutral-400 font-medium">최근 7일</span>
        </div>
        <div class="mt-5 flex items-end justify-between gap-2 h-36">
            @foreach ($weekly as $d)
                @php $h = max(4, (int) round($d['amount'] / $maxAmt * 100)); @endphp
                <div class="flex-1 flex flex-col items-center gap-1.5 group relative">
                    @if ($d['amount'] > 0)
                        <span class="absolute -top-5 text-[10px] font-bold text-mango-600 opacity-0 group-hover:opacity-100 transition whitespace-nowrap">{{ number_format($d['amount']) }}</span>
                    @endif
                    <div class="w-full flex items-end justify-center" style="height:100px">
                        <div class="w-full max-w-[26px] rounded-t-md bg-gradient-to-t from-mango-500 to-mango-300 transition-all group-hover:from-mango-600 group-hover:to-mango-400" style="height:{{ $h }}%"></div>
                    </div>
                    <span class="text-[11px] text-neutral-400">{{ $d['dow'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="mt-5 pt-4 border-t border-neutral-50 flex items-center justify-around text-center">
            <div>
                <p class="text-xs text-neutral-400 font-medium">7일 발주 금액</p>
                <p class="text-lg font-black text-neutral-900 mt-0.5">{{ number_format($weekTotalAmt) }}<span class="text-sm text-neutral-400">원</span></p>
            </div>
            <div class="w-px h-8 bg-neutral-100"></div>
            <div>
                <p class="text-xs text-neutral-400 font-medium">7일 발주 건수</p>
                <p class="text-lg font-black text-neutral-900 mt-0.5">{{ number_format($weekTotalCnt) }}<span class="text-sm text-neutral-400">건</span></p>
            </div>
        </div>
    </div>

    {{-- 매장 지표 (우리가게NOW 대응) --}}
    <div class="rounded-2xl bg-white border border-neutral-100 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <span class="font-extrabold text-neutral-900">우리 매장 현황</span>
            <span class="text-[11px] text-neutral-400">실시간</span>
        </div>
        <div class="mt-4 space-y-3">
            @php
                $metrics = [
                    ['진행 중 발주', $stats['orders_active'], '건', 'text-mango-600', route('portal.store.orders.index')],
                    ['배송 중', $stats['orders_shipping'], '건', 'text-sky-600', route('portal.store.inbound')],
                    ['입고 대기', $stats['inbound_waiting'], '건', 'text-indigo-600', route('portal.store.inbound')],
                    ['미확인 명세서', $stats['statements_unconfirmed'], '건', 'text-amber-600', route('portal.store.statements.index')],
                    ['완료 발주', $stats['orders_completed'], '건', 'text-emerald-600', route('portal.store.orders.index')],
                ];
            @endphp
            @foreach ($metrics as [$label, $val, $unit, $color, $href])
                <a href="{{ $href }}" data-ws-tab class="flex items-center justify-between py-1.5 group">
                    <span class="text-sm text-neutral-600 group-hover:text-neutral-900">{{ $label }}</span>
                    <span class="text-lg font-black {{ $val > 0 ? $color : 'text-neutral-300' }}">{{ number_format($val) }}<span class="text-xs font-bold ml-0.5">{{ $unit }}</span></span>
                </a>
            @endforeach
        </div>
        @if ($stats['statements_unconfirmed'] > 0)
            <div class="mt-4 rounded-xl bg-amber-50 border border-amber-100 px-4 py-3 text-xs text-amber-700 font-medium">
                확인하지 않은 거래명세서가 {{ $stats['statements_unconfirmed'] }}건 있어요. 확인해 주세요.
            </div>
        @endif
    </div>
</div>

{{-- ===== 최근 발주 내역 ===== --}}
<div class="rounded-2xl bg-white border border-neutral-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-50">
        <h2 class="font-extrabold text-neutral-900 flex items-center gap-2"><span class="w-1 h-4 rounded bg-mango-500"></span>최근 발주 내역</h2>
        <a href="{{ route('portal.store.orders.index') }}" data-ws-tab class="text-sm font-bold text-mango-600 hover:text-mango-700">전체보기 →</a>
    </div>
    @if ($recentOrders->isEmpty())
        <p class="px-6 py-12 text-center text-neutral-400">발주 내역이 없습니다.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">주문번호</th>
                    <th class="text-right font-semibold px-6 py-3">결제금액</th>
                    <th class="text-left font-semibold px-6 py-3">상태</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">발주일</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($recentOrders as $o)
                    <tr class="hover:bg-mango-50/40 transition cursor-pointer" onclick="wsGo('{{ route('portal.store.orders.show', $o) }}')">
                        <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $o->order_no }}</td>
                        <td class="px-6 py-3.5 text-right font-semibold">{{ number_format($o->store_amount) }}원</td>
                        <td class="px-6 py-3.5">@include('portal.partials.order-status', ['status' => $o->status, 'label' => $o->status_label])</td>
                        <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $o->created_at->format('Y.m.d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
