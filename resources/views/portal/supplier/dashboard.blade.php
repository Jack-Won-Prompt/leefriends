@extends('portal.layout')
@section('title', '홈')

@php
    use Illuminate\Support\Carbon;
    $user = auth()->user();
    $supplier = $user->supplier;
    $today = Carbon::now();
    $dow = ['일', '월', '화', '수', '목', '금', '토'][$today->dayOfWeek];
    $unread = $user->notifications()->whereNull('read_at')->count();
    $recentNotis = $user->notifications()->take(4)->get();
    $maxCnt = max(1, collect($weekly)->max('count'));
    $weekTotalCnt = collect($weekly)->sum('count');

    $quick = [
        ['판매주문', route('portal.supplier.sales_orders.index'), 'accent', '<path d="M6 6h15l-1.5 9h-12z"/><path d="M6 6L5 3H2"/><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/>'],
        ['출고 관리', route('portal.supplier.shipments.index'), 'info', '<path d="M3 6.5A1.5 1.5 0 0 1 4.5 5H13a1 1 0 0 1 1 1v9H3z"/><path d="M14 9h3.6a1 1 0 0 1 .8.4L21 13v2h-7z"/><circle cx="7" cy="18" r="1.7"/><circle cx="17.5" cy="18" r="1.7"/>'],
        ['계산서 발행', route('portal.supplier.invoices.create'), 'warn', '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 3v18M8 8h12M8 13h12"/>'],
        ['계산서 내역', route('portal.supplier.invoices.index'), 'default', '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/>'],
        ['매출', route('portal.supplier.sales'), 'success', '<path d="M4 4v15a1 1 0 0 0 1 1h15"/><path d="M7 14l3.5-4 3 3L20 7"/>'],
        ['품목', route('portal.supplier.products.index'), 'accent', '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/>'],
    ];
    $tileGrad = [
        'accent' => 'from-mango-400 to-mango-600', 'info' => 'from-sky-400 to-indigo-500',
        'success' => 'from-emerald-400 to-teal-600', 'warn' => 'from-amber-400 to-orange-500',
        'default' => 'from-slate-400 to-slate-600',
    ];
@endphp

@section('content')
{{-- 상단: 인사·요약 | 공지·알림 --}}
<div class="grid lg:grid-cols-3 gap-4 mb-4">
    <div class="lg:col-span-2 rounded-2xl bg-white border border-neutral-100 shadow-sm p-6">
        <p class="text-sm text-neutral-400 font-medium">{{ $today->format('Y. n. j') }} ({{ $dow }})</p>
        <h2 class="text-xl font-black text-neutral-900 mt-1">{{ $user->name }} 님, 오늘도 좋은 하루 되세요!</h2>
        @if ($supplier)<p class="text-sm text-neutral-500 mt-0.5">공급처 · {{ $supplier->name }}</p>@endif

        <a href="{{ route('portal.supplier.sales_orders.index') }}" data-ws-tab
           class="mt-4 flex items-center justify-between rounded-xl bg-mango-50 border border-mango-100 px-5 py-4 hover:bg-mango-100/70 transition group">
            <span class="text-sm font-bold text-mango-700">처리할 배송</span>
            <span class="text-2xl font-black {{ $stats['pending'] > 0 ? 'text-rose-600' : 'text-mango-700' }} group-hover:translate-x-0.5 transition">
                {{ number_format($stats['pending']) }}<span class="text-base font-bold">건</span>
                <span class="text-mango-400 ml-1">›</span>
            </span>
        </a>

        <div class="grid grid-cols-2 gap-3 mt-3">
            <a href="{{ route('portal.supplier.shipments.index', ['status' => 'confirmed']) }}" data-ws-tab
               class="rounded-xl border border-neutral-100 bg-neutral-50/60 px-5 py-4 hover:border-mango-200 hover:bg-mango-50/40 transition">
                <p class="text-xs text-neutral-500 font-medium">배송 중</p>
                <p class="text-xl font-black text-neutral-900 mt-1">{{ number_format($stats['shipping']) }}<span class="text-sm font-bold text-neutral-400 ml-0.5">건</span></p>
            </a>
            <a href="{{ route('portal.supplier.invoices.create') }}" data-ws-tab
               class="rounded-xl border border-neutral-100 bg-neutral-50/60 px-5 py-4 hover:border-amber-200 hover:bg-amber-50/40 transition">
                <p class="text-xs text-neutral-500 font-medium">미청구 (배송완료)</p>
                <p class="text-xl font-black {{ $stats['uninvoiced'] > 0 ? 'text-amber-600' : 'text-neutral-900' }} mt-1">{{ number_format($stats['uninvoiced']) }}<span class="text-sm font-bold text-neutral-400 ml-0.5">건</span></p>
            </a>
        </div>
    </div>

    <div class="rounded-2xl bg-white border border-neutral-100 shadow-sm overflow-hidden flex flex-col">
        <div class="px-5 pt-4 pb-3 border-b border-neutral-50">
            <span class="flex items-center gap-1 font-extrabold text-neutral-900">공지사항</span>
            <div class="mt-2 space-y-2">
                @forelse ($notices as $n)
                    <div class="flex items-start gap-2">
                        @if ($n->is_pinned)<span class="mt-0.5 text-[10px] font-bold text-white bg-rose-500 rounded px-1.5 py-0.5 shrink-0">중요</span>@endif
                        <span class="text-sm text-neutral-700 line-clamp-1 flex-1">{{ $n->title }}</span>
                        <span class="text-[11px] text-neutral-300 shrink-0">{{ optional($n->published_at)->format('n.j') }}</span>
                    </div>
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

{{-- 빠른 실행 --}}
<div class="rounded-2xl bg-white border border-neutral-100 shadow-sm p-5 mb-4">
    <p class="text-sm font-extrabold text-neutral-500 mb-3">빠른 실행</p>
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
        @foreach ($quick as [$label, $href, $variant, $path])
            <a href="{{ $href }}" data-ws-tab class="flex flex-col items-center gap-2 rounded-xl py-4 px-2 hover:bg-neutral-50 transition group">
                <span class="w-12 h-12 rounded-2xl bg-gradient-to-br {{ $tileGrad[$variant] }} grid place-items-center text-white shadow-sm group-hover:scale-105 transition">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $path !!}</svg>
                </span>
                <span class="text-xs font-bold text-neutral-700 text-center leading-tight">{{ $label }}</span>
            </a>
        @endforeach
    </div>
</div>

{{-- 주간 출고 추이 | 현황 --}}
<div class="grid lg:grid-cols-3 gap-4 mb-4">
    <div class="lg:col-span-2 rounded-2xl bg-white border border-neutral-100 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <a href="{{ route('portal.supplier.sales_orders.index') }}" data-ws-tab class="flex items-center gap-1 font-extrabold text-neutral-900 hover:text-mango-600">
                출고 요청 건수 <span class="text-neutral-300">›</span>
            </a>
            <span class="text-xs text-neutral-400 font-medium">최근 7일</span>
        </div>
        <div class="mt-5 flex items-end justify-between gap-2 h-36">
            @foreach ($weekly as $d)
                @php $h = max(4, (int) round($d['count'] / $maxCnt * 100)); @endphp
                <div class="flex-1 flex flex-col items-center gap-1.5 group relative">
                    @if ($d['count'] > 0)
                        <span class="absolute -top-5 text-[10px] font-bold text-mango-600 opacity-0 group-hover:opacity-100 transition">{{ $d['count'] }}건</span>
                    @endif
                    <div class="w-full flex items-end justify-center" style="height:100px">
                        <div class="w-full max-w-[26px] rounded-t-md bg-gradient-to-t from-mango-500 to-mango-300 transition-all group-hover:from-mango-600 group-hover:to-mango-400" style="height:{{ $h }}%"></div>
                    </div>
                    <span class="text-[11px] text-neutral-400">{{ $d['dow'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="mt-5 pt-4 border-t border-neutral-50 text-center">
            <p class="text-xs text-neutral-400 font-medium">최근 7일 출고 요청</p>
            <p class="text-lg font-black text-neutral-900 mt-0.5">{{ number_format($weekTotalCnt) }}<span class="text-sm text-neutral-400">건</span></p>
        </div>
    </div>

    <div class="rounded-2xl bg-white border border-neutral-100 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <span class="font-extrabold text-neutral-900">현황</span>
            <span class="text-[11px] text-neutral-400">실시간</span>
        </div>
        <div class="mt-4 space-y-3">
            @php
                $metrics = [
                    ['배송 대기', $stats['pending'], '건', 'text-rose-600', route('portal.supplier.sales_orders.index')],
                    ['배송 중', $stats['shipping'], '건', 'text-sky-600', route('portal.supplier.shipments.index', ['status' => 'confirmed'])],
                    ['미청구', $stats['uninvoiced'], '건', 'text-amber-600', route('portal.supplier.invoices.create')],
                    ['발행 계산서', $stats['invoices'], '건', 'text-emerald-600', route('portal.supplier.invoices.index')],
                    ['이번 달 출고 요청', $stats['month_count'], '건', 'text-indigo-600', route('portal.supplier.sales_orders.index')],
                ];
            @endphp
            @foreach ($metrics as [$label, $val, $unit, $color, $href])
                <a href="{{ $href }}" data-ws-tab class="flex items-center justify-between py-1.5 group">
                    <span class="text-sm text-neutral-600 group-hover:text-neutral-900">{{ $label }}</span>
                    <span class="text-lg font-black {{ $val > 0 ? $color : 'text-neutral-300' }}">{{ number_format($val) }}<span class="text-xs font-bold ml-0.5">{{ $unit }}</span></span>
                </a>
            @endforeach
        </div>
        @if ($stats['pending'] > 0)
            <div class="mt-4 rounded-xl bg-rose-50 border border-rose-100 px-4 py-3 text-xs text-rose-700 font-medium">
                배송 대기 중인 주문이 {{ $stats['pending'] }}건 있어요. 출고를 진행해 주세요.
            </div>
        @endif
    </div>
</div>

{{-- 최근 주문 품목 --}}
<div class="rounded-2xl bg-white border border-neutral-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-50">
        <h2 class="font-extrabold text-neutral-900 flex items-center gap-2"><span class="w-1 h-4 rounded bg-mango-500"></span>최근 주문 품목 (매장 직배송)</h2>
        <a href="{{ route('portal.supplier.shipments.index') }}" data-ws-tab class="text-sm font-bold text-mango-600 hover:text-mango-700">출고 관리 →</a>
    </div>
    @if ($recentItems->isEmpty())
        <p class="px-6 py-12 text-center text-neutral-400">배송할 주문 품목이 없습니다.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">주문번호</th>
                    <th class="text-left font-semibold px-6 py-3">배송지(매장)</th>
                    <th class="text-left font-semibold px-6 py-3">품목</th>
                    <th class="text-right font-semibold px-6 py-3">수량</th>
                    <th class="text-left font-semibold px-6 py-3">배송상태</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($recentItems as $it)
                    <tr class="hover:bg-mango-50/40 transition">
                        <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $it->order->order_no ?? '-' }}</td>
                        <td class="px-6 py-3.5">{{ $it->order->store->name ?? '-' }}</td>
                        <td class="px-6 py-3.5">{{ $it->product_name }}</td>
                        <td class="px-6 py-3.5 text-right">{{ number_format($it->qty) }}{{ $it->unit }}</td>
                        <td class="px-6 py-3.5">@include('portal.partials.fulfillment-status', ['status' => $it->fulfillment_status, 'label' => $it->fulfillment_label])</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
