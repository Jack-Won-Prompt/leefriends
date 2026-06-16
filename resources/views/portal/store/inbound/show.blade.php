@extends('portal.layout')
@section('title', '입고 처리')

@section('content')
<a href="{{ route('portal.store.inbound') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600 mb-5">← 입고예정·배송</a>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-7 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-black text-neutral-900">{{ $shipment->shipment_no }}</h2>
            <p class="text-sm text-neutral-400 mt-1">{{ $shipment->seller_name }} · {{ $shipment->carrier }} {{ $shipment->tracking_no }}</p>
        </div>
        @include('portal.partials.lifecycle-status', ['status' => $shipment->status, 'label' => $shipment->status_label])
    </div>

    @if ($shipment->status === 'confirmed')
        <div class="mt-6 rounded-xl bg-emerald-50 border border-emerald-200 p-5">
            <p class="text-sm text-emerald-800 mb-3">제품을 받으셨다면 인수확인 후 입고완료 처리하세요. 재고에 자동 반영됩니다.
                <span class="block text-emerald-600 mt-1">* 모바일 앱에서는 거래명세서 바코드 스캔으로 처리됩니다.</span>
            </p>
            <form method="POST" action="{{ route('portal.store.shipments.receive', $shipment) }}"
                  onsubmit="return confirm('이 출고를 인수확인하고 입고완료 처리할까요? 재고가 증가합니다.')">
                @csrf
                <button class="rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-7 py-3 transition">✅ 인수확인 · 입고완료</button>
            </form>
        </div>
    @elseif ($shipment->status === 'received')
        <div class="mt-6 rounded-xl bg-neutral-50 border border-neutral-200 p-5 text-center">
            <p class="font-bold text-emerald-700">입고완료</p>
            <p class="text-sm text-neutral-500 mt-1">{{ $shipment->received_at?->format('Y.m.d H:i') }} · 재고 반영됨</p>
        </div>
    @endif
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">입고 품목</div>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-6 py-3">품목</th>
                <th class="text-left font-semibold px-6 py-3">단위</th>
                <th class="text-right font-semibold px-6 py-3">수량</th>
                <th class="text-left font-semibold px-6 py-3">상태</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @foreach ($shipment->items as $it)
                <tr>
                    <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $it->product_name }}</td>
                    <td class="px-6 py-3.5 text-neutral-500">{{ $it->unit }}</td>
                    <td class="px-6 py-3.5 text-right">{{ number_format($it->qty) }}</td>
                    <td class="px-6 py-3.5">@include('portal.partials.fulfillment-status', ['status' => $it->fulfillment_status, 'label' => $it->fulfillment_label])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
