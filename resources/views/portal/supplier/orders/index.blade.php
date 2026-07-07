@extends('portal.layout')
@section('title', '주문 관리')

@section('content')
<x-wms.page-head title="주문 관리" subtitle="자사 품목이 포함된 매장 주문을 조회합니다" icon="📦" />

<x-wms.filter :action="route('portal.supplier.orders.index')">
    <x-wms.field label="매장(배송지)">
        <select name="store" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="all">전체 매장</option>
            @foreach ($stores as $s)
                <option value="{{ $s->id }}" @selected((string) $store === (string) $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </x-wms.field>
    <x-wms.field label="발주 시작일">
        <input type="date" name="from" value="{{ $from ?? '' }}" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
    </x-wms.field>
    <x-wms.field label="발주 종료일">
        <input type="date" name="to" value="{{ $to ?? '' }}" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
    </x-wms.field>
</x-wms.filter>

<x-wms.toolbar :count="$orders->total()" />

<x-wms.panel>
    @if ($orders->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">접수된 주문이 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">주문번호</th>
                        <th class="text-left font-semibold px-6 py-3">매장</th>
                        <th class="text-left font-semibold px-6 py-3 hidden lg:table-cell">배송지 주소</th>
                        <th class="text-right font-semibold px-6 py-3">자사 품목</th>
                        <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">공급액</th>
                        <th class="text-left font-semibold px-6 py-3">배송현황</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">접수일</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($orders as $o)
                        @php
                            $items = $o->items;
                            $supplyAmt = $items->sum('supply_line_amount');
                            $delivered = $items->where('fulfillment_status', 'delivered')->count();
                            $total = $items->count();
                            $fStat = $delivered === $total ? 'delivered' : ($items->whereIn('fulfillment_status', ['shipping','delivered'])->count() > 0 ? 'shipping' : 'pending');
                            $fLabel = $delivered === $total ? '배송완료' : ($fStat === 'shipping' ? "진행중 ($delivered/$total)" : '대기');
                        @endphp
                        <tr class="hover:bg-mango-50/40 transition cursor-pointer" onclick="location.href='{{ route('portal.supplier.orders.show', $o) }}'">
                            <td class="px-6 py-3.5 font-bold text-neutral-900 font-mono">{{ $o->order_no }}</td>
                            <td class="px-6 py-3.5 font-semibold">{{ $o->store->name ?? '-' }}</td>
                            <td class="px-6 py-3.5 hidden lg:table-cell text-neutral-500">{{ $o->store->address ?? '-' }}</td>
                            <td class="px-6 py-3.5 text-right">{{ number_format($total) }}개</td>
                            <td class="px-6 py-3.5 text-right hidden md:table-cell font-semibold">{{ number_format($supplyAmt) }}원</td>
                            <td class="px-6 py-3.5">@include('portal.partials.fulfillment-status', ['status' => $fStat, 'label' => $fLabel])</td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $o->created_at->format('Y.m.d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-wms.panel>

<div class="mt-5">{{ $orders->links() }}</div>
@endsection
