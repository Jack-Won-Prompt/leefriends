@extends('portal.layout')
@section('title', '판매주문')

@section('content')
@php($asModal = $asModal ?? false)
<div x-data="{ open: null }">
<x-wms.page-head title="판매주문" subtitle="구매주문에서 분할된 판매주문을 확인·처리합니다" icon="🧾" />

<x-wms.filter :action="route($routePrefix . '.sales_orders.index')">
    <x-wms.field label="진행상태">
        <select name="status" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="all">전체</option>
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </x-wms.field>
    <x-wms.field label="매장">
        <select name="store" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="all">전체 매장</option>
            @foreach ($stores as $s)
                <option value="{{ $s->id }}" @selected((string) $store === (string) $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </x-wms.field>
</x-wms.filter>

<x-wms.toolbar :count="$salesOrders->total()" />

<x-wms.panel>
    @if ($salesOrders->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">판매주문이 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">판매주문번호</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">구매주문번호</th>
                        <th class="text-left font-semibold px-6 py-3">매장</th>
                        <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">품목</th>
                        <th class="text-right font-semibold px-6 py-3">공급액</th>
                        <th class="text-left font-semibold px-6 py-3">상태</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">접수일</th>
                        <th class="text-right font-semibold px-6 py-3 w-32">처리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($salesOrders as $so)
                        <tr class="hover:bg-mango-50/40 transition">
                            <td class="px-6 py-3.5 font-bold text-neutral-900">
                                @if ($asModal)
                                    <button type="button" @click="open = {{ $so->id }}" class="hover:text-mango-600 font-mono">{{ $so->sales_order_no }}</button>
                                @else
                                    <a href="{{ route($routePrefix . '.sales_orders.show', $so) }}" class="hover:text-mango-600 font-mono">{{ $so->sales_order_no }}</a>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 hidden md:table-cell font-mono text-neutral-500">{{ $so->order->order_no ?? '-' }}</td>
                            <td class="px-6 py-3.5">{{ $so->store->name ?? '-' }}</td>
                            <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ $so->item_count }}</td>
                            <td class="px-6 py-3.5 text-right font-semibold">{{ number_format($so->supply_amount) }}원</td>
                            <td class="px-6 py-3.5">@include('portal.partials.lifecycle-status', ['status' => $so->status, 'label' => $so->status_label])</td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $so->created_at->format('Y.m.d H:i') }}</td>
                            <td class="px-6 py-3.5 text-right">
                                @if ($so->status === 'created')
                                    <form method="POST" action="{{ route($routePrefix . '.sales_orders.confirm', $so) }}">
                                        @csrf @method('PATCH')
                                        <button class="rounded-lg bg-mango-500 hover:bg-mango-600 text-white px-3 py-1.5 font-semibold">판매주문 확인</button>
                                    </form>
                                @elseif ($asModal)
                                    <button type="button" @click="open = {{ $so->id }}" class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1.5 font-semibold inline-block">상세</button>
                                @else
                                    <a href="{{ route($routePrefix . '.sales_orders.show', $so) }}" class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1.5 font-semibold inline-block">상세</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-wms.panel>

<div class="mt-5">{{ $salesOrders->links() }}</div>

@if ($asModal)
    {{-- 판매주문 상세 팝업 --}}
    @foreach ($salesOrders as $so)
        <x-detail-modal :id="$so->id">
            @include('portal.partials.sales-order-detail', ['salesOrder' => $so, 'routePrefix' => $routePrefix])
        </x-detail-modal>
    @endforeach
@endif
</div>
@endsection
