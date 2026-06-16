@extends('portal.layout')
@section('title', '대시보드')

@section('content')
<x-wms.page-head title="대시보드" subtitle="본사 · 처리 현황" icon="📊">
    <x-slot:actions>
        <span class="text-sm text-neutral-500">{{ auth()->user()->name }} 님 환영합니다</span>
    </x-slot:actions>
</x-wms.page-head>

{{-- KPI --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <x-wms.stat label="접수 대기 발주" :value="number_format($stats['orders_pending'])" icon="🔔" variant="danger" :href="route('portal.hq.orders.index', ['status' => 'pending'])" />
    <x-wms.stat label="전체 발주" :value="number_format($stats['orders_total'])" icon="📦" variant="accent" :href="route('portal.hq.orders.index')" />
    <x-wms.stat label="품목" :value="number_format($stats['products'])" icon="🏷️" variant="warn" :href="route('portal.hq.products.index')" />
    <x-wms.stat label="공급처" :value="number_format($stats['suppliers'])" icon="🏭" variant="info" :href="route('portal.hq.suppliers.index')" />
    <x-wms.stat label="매장" :value="number_format($stats['stores'])" icon="🏬" variant="success" />
</div>

<div class="grid lg:grid-cols-3 gap-6">
    {{-- 최근 발주 --}}
    <div class="lg:col-span-2">
        <x-wms.panel title="최근 발주">
            <x-slot:actions>
                <a href="{{ route('portal.hq.orders.index') }}" class="text-sm font-bold text-mango-600 hover:text-mango-700">전체보기 →</a>
            </x-slot:actions>
            @if ($recentOrders->isEmpty())
                <p class="px-6 py-12 text-center text-neutral-400">접수된 발주가 없습니다.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-neutral-50 text-neutral-500">
                        <tr>
                            <th class="text-left font-semibold px-6 py-3">주문번호</th>
                            <th class="text-left font-semibold px-6 py-3">매장</th>
                            <th class="text-right font-semibold px-6 py-3">출고가 합계</th>
                            <th class="text-left font-semibold px-6 py-3">상태</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($recentOrders as $o)
                            <tr class="hover:bg-mango-50/40 transition cursor-pointer" onclick="location.href='{{ route('portal.hq.orders.show', $o) }}'">
                                <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $o->order_no }}</td>
                                <td class="px-6 py-3.5">{{ $o->store->name ?? '-' }}</td>
                                <td class="px-6 py-3.5 text-right font-semibold">{{ number_format($o->store_amount) }}원</td>
                                <td class="px-6 py-3.5">@include('portal.partials.order-status', ['status' => $o->status, 'label' => $o->status_label])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-wms.panel>
    </div>

    {{-- 알림 --}}
    <div>
        <x-wms.panel title="최근 알림">
            @php $myNotis = auth()->user()->notifications()->take(6)->get(); @endphp
            @forelse ($myNotis as $n)
                <div class="px-6 py-3 border-b border-neutral-50 last:border-0">
                    <p class="text-sm font-bold text-neutral-800">{{ $n->title }}</p>
                    <p class="text-xs text-neutral-400 mt-0.5">{{ $n->created_at->diffForHumans() }}</p>
                </div>
            @empty
                <p class="px-6 py-12 text-center text-neutral-400 text-sm">알림이 없습니다.</p>
            @endforelse
        </x-wms.panel>
    </div>
</div>
@endsection
