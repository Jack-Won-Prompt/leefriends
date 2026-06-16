@extends('portal.layout')
@section('title', '대시보드')

@section('content')
<x-wms.page-head title="대시보드" :subtitle="$user->store->name ?? '매장'" icon="📊">
    <x-slot:actions>
        <a href="{{ route('portal.store.orders.create') }}" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">🛒 재료 발주하기</a>
    </x-slot:actions>
</x-wms.page-head>

<div class="grid grid-cols-3 gap-4 mb-6">
    <x-wms.stat label="전체 발주" :value="number_format($stats['orders_total'])" icon="📦" variant="accent" :href="route('portal.store.orders.index')" />
    <x-wms.stat label="배송중" :value="number_format($stats['orders_shipping'])" icon="🚚" variant="info" :href="route('portal.store.inbound')" />
    <x-wms.stat label="완료" :value="number_format($stats['orders_completed'])" icon="✅" variant="success" />
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <x-wms.panel title="최근 발주 내역">
            <x-slot:actions>
                <a href="{{ route('portal.store.orders.index') }}" class="text-sm font-bold text-mango-600 hover:text-mango-700">전체보기 →</a>
            </x-slot:actions>
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
                            <tr class="hover:bg-mango-50/40 transition cursor-pointer" onclick="location.href='{{ route('portal.store.orders.show', $o) }}'">
                                <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $o->order_no }}</td>
                                <td class="px-6 py-3.5 text-right font-semibold">{{ number_format($o->store_amount) }}원</td>
                                <td class="px-6 py-3.5">@include('portal.partials.order-status', ['status' => $o->status, 'label' => $o->status_label])</td>
                                <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $o->created_at->format('Y.m.d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-wms.panel>
    </div>
    <div>
        <x-wms.panel title="최근 알림">
            @php $myNotis = auth()->user()->notifications()->take(6)->get(); @endphp
            @forelse ($myNotis as $n)
                <div class="px-6 py-3 border-b border-neutral-50 last:border-0">
                    <p class="text-sm font-bold text-neutral-800">{{ $n->title }}</p>
                    <p class="text-xs text-neutral-500 mt-0.5">{{ $n->body }}</p>
                    <p class="text-[11px] text-neutral-400 mt-0.5">{{ $n->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <p class="px-6 py-12 text-center text-neutral-400 text-sm">알림이 없습니다.</p>
            @endforelse
        </x-wms.panel>
    </div>
</div>
@endsection
