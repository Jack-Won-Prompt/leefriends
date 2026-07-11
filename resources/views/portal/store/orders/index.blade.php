@extends('portal.layout')
@section('title', '발주 내역')

@section('content')
<x-wms.page-head title="발주 내역" subtitle="우리 매장의 발주 내역을 조회합니다" icon="📦">
    <x-slot:actions>
        <a href="{{ route('portal.store.orders.create') }}" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">🛒 재료 발주하기</a>
    </x-slot:actions>
</x-wms.page-head>

@if ($store && $store->settlement_type === 'prepaid')
    <div class="mb-5 flex items-center justify-between rounded-2xl p-5 text-white {{ $store->ledger_balance < 0 ? 'bg-gradient-to-br from-rose-500 to-rose-600' : 'bg-gradient-to-br from-emerald-500 to-emerald-600' }}">
        <div>
            <p class="text-white/80 font-semibold text-sm">{{ $store->ledger_balance < 0 ? '미수금 (예치 잔액 부족)' : '예치금 잔액' }}</p>
            <p class="text-2xl font-black mt-0.5">{{ number_format(abs($store->ledger_balance)) }}<span class="text-base">원</span></p>
        </div>
        <p class="text-xs text-white/80 text-right leading-relaxed">발주 시 예치금에서<br>자동 차감됩니다</p>
    </div>
@endif

<x-date-filter :from="$from" :to="$to" label="발주일 기간" />

<x-wms.toolbar :count="$orders->total()" />

<x-wms.panel>
    @if ($orders->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">발주 내역이 없습니다.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">주문번호</th>
                    <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">품목수</th>
                    <th class="text-right font-semibold px-6 py-3">결제금액</th>
                    <th class="text-left font-semibold px-6 py-3">상태</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">발주일</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($orders as $o)
                    <tr class="hover:bg-mango-50/40 transition cursor-pointer" onclick="location.href='{{ route('portal.store.orders.show', $o) }}'">
                        <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $o->order_no }}</td>
                        <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ $o->items_count }}</td>
                        <td class="px-6 py-3.5 text-right font-semibold">{{ number_format($o->store_amount) }}원</td>
                        <td class="px-6 py-3.5">
                            <div class="flex items-center gap-1.5">
                                @include('portal.partials.order-status', ['status' => $o->status, 'label' => $o->status_label])
                                @include('portal.partials.payment-badge', ['order' => $o])
                            </div>
                        </td>
                        <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $o->created_at->format('Y.m.d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-wms.panel>

<div class="mt-5">{{ $orders->links() }}</div>
@endsection
