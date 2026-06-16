@extends('portal.layout')
@section('title', '대시보드')

@section('content')
<x-wms.page-head title="대시보드" :subtitle="'공급처 · ' . (auth()->user()->supplier->name ?? '')" icon="📊">
    <x-slot:actions>
        <span class="text-sm text-neutral-500">{{ auth()->user()->name }} 님 환영합니다</span>
    </x-slot:actions>
</x-wms.page-head>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <x-wms.stat label="배송 대기" :value="number_format($stats['pending'])" icon="📥" variant="danger" :href="route('portal.supplier.sales_orders.index')" />
    <x-wms.stat label="배송중" :value="number_format($stats['shipping'])" icon="🚚" variant="info" :href="route('portal.supplier.shipments.index', ['status' => 'confirmed'])" />
    <x-wms.stat label="미청구(배송완료)" :value="number_format($stats['uninvoiced'])" icon="🧾" variant="warn" :href="route('portal.supplier.invoices.create')" />
    <x-wms.stat label="발행 계산서" :value="number_format($stats['invoices'])" icon="🧮" variant="success" :href="route('portal.supplier.invoices.index')" />
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <x-wms.panel title="최근 주문 품목 (매장 직배송)">
            <x-slot:actions>
                <a href="{{ route('portal.supplier.shipments.index') }}" class="text-sm font-bold text-mango-600 hover:text-mango-700">출고 관리 →</a>
            </x-slot:actions>
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
        </x-wms.panel>
    </div>
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
