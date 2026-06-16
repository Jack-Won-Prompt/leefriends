@extends('portal.layout')
@php $isSample = $order->isSample(); @endphp
@section('title', $isSample ? '샘플 주문 상세' : '발주 상세')

@section('content')
<div class="flex items-center justify-between mb-5">
    @if ($isSample)
        <a href="{{ route('portal.store.sample_orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-violet-600">← 샘플 주문 내역</a>
    @else
        <a href="{{ route('portal.store.orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600">← 발주 내역</a>
    @endif
    @if (! empty($editable))
        <div class="flex gap-2">
            <a href="{{ route('portal.store.orders.edit', $order) }}" class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-4 py-2 font-bold text-sm">✏️ 수정</a>
            <form method="POST" action="{{ route('portal.store.orders.destroy', $order) }}" onsubmit="return confirm('이 발주를 취소할까요? 본사·공급처에 취소 알림이 전송됩니다.')">
                @csrf @method('DELETE')
                <button class="rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 px-4 py-2 font-bold text-sm">발주 취소</button>
            </form>
        </div>
    @endif
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-7 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-2xl font-black text-neutral-900">{{ $order->order_no }}</h2>
                @if ($isSample)<span class="text-xs font-bold px-2.5 py-1 rounded-full bg-violet-100 text-violet-700">샘플 · 무상</span>@endif
            </div>
            <p class="text-sm text-neutral-400 mt-1">{{ $isSample ? '주문일' : '발주일' }} {{ $order->created_at->format('Y년 m월 d일 H:i') }}</p>
        </div>
        @include('portal.partials.order-status', ['status' => $order->status, 'label' => $order->status_label])
    </div>
    @if ($order->note)
        <p class="mt-4 text-sm text-neutral-600 bg-neutral-50 rounded-xl p-4">📝 {{ $order->note }}</p>
    @endif
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">발주 품목 · 배송현황</div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">품목</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">공급</th>
                    @unless ($isSample)<th class="text-right font-semibold px-6 py-3">단가</th>@endunless
                    <th class="text-right font-semibold px-6 py-3">수량</th>
                    @unless ($isSample)<th class="text-right font-semibold px-6 py-3">금액</th>@endunless
                    <th class="text-left font-semibold px-6 py-3">배송</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($order->items as $it)
                    <tr>
                        <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $it->product_name }}</td>
                        <td class="px-6 py-3.5 hidden md:table-cell">
                            @if ($it->supply_type === 'supplier')
                                <span class="text-xs font-bold text-sky-700">{{ $it->supplier_name }} (직배송)</span>
                            @else
                                <span class="text-xs font-bold text-mango-700">본사</span>
                            @endif
                        </td>
                        @unless ($isSample)<td class="px-6 py-3.5 text-right">{{ number_format($it->store_unit_price) }}원</td>@endunless
                        <td class="px-6 py-3.5 text-right">{{ number_format($it->qty) }}{{ $it->unit }}</td>
                        @unless ($isSample)<td class="px-6 py-3.5 text-right font-semibold">{{ number_format($it->store_line_amount) }}원</td>@endunless
                        <td class="px-6 py-3.5">@include('portal.partials.fulfillment-status', ['status' => $it->fulfillment_status, 'label' => $it->fulfillment_label])</td>
                    </tr>
                @endforeach
            </tbody>
            @unless ($isSample)
            <tfoot>
                <tr class="bg-neutral-50 font-black">
                    <td class="px-6 py-4" colspan="4">합계</td>
                    <td class="px-6 py-4 text-right text-mango-700 text-lg">{{ number_format($order->store_amount) }}원</td>
                    <td></td>
                </tr>
            </tfoot>
            @endunless
        </table>
    </div>
</div>
@endsection
