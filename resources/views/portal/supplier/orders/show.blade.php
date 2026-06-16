@extends('portal.layout')
@section('title', '주문 상세')

@section('content')
<a href="{{ route('portal.supplier.orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600 mb-5">← 주문 관리</a>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-7 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-black text-neutral-900">{{ $order->order_no }}</h2>
            <p class="text-sm text-neutral-400 mt-1">{{ $order->created_at->format('Y년 m월 d일 H:i') }}</p>
        </div>
        @include('portal.partials.order-status', ['status' => $order->status, 'label' => $order->status_label])
    </div>
    <dl class="grid sm:grid-cols-2 gap-x-8 gap-y-3 mt-6 text-sm">
        <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">배송지 (매장)</dt><dd class="font-bold">{{ $order->store->name ?? '-' }}</dd></div>
        <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">연락처</dt><dd>{{ $order->store->phone ?? '-' }}</dd></div>
        <div class="flex justify-between border-b border-neutral-100 pb-2 sm:col-span-2"><dt class="text-neutral-500 font-semibold">주소</dt><dd class="text-right">{{ $order->store->address ?? '-' }}</dd></div>
    </dl>
    @if ($order->note)
        <p class="mt-4 text-sm text-neutral-600 bg-neutral-50 rounded-xl p-4">📝 {{ $order->note }}</p>
    @endif
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">자사 공급 품목 (매장 직배송)</div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">품목</th>
                    <th class="text-right font-semibold px-6 py-3">공급단가</th>
                    <th class="text-right font-semibold px-6 py-3">수량</th>
                    <th class="text-right font-semibold px-6 py-3">공급액</th>
                    <th class="text-left font-semibold px-6 py-3">상태</th>
                    <th class="text-right font-semibold px-6 py-3 w-56">배송 처리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($order->items as $it)
                    <tr>
                        <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $it->product_name }}</td>
                        <td class="px-6 py-3.5 text-right">{{ number_format($it->supply_unit_price) }}원</td>
                        <td class="px-6 py-3.5 text-right">{{ number_format($it->qty) }}{{ $it->unit }}</td>
                        <td class="px-6 py-3.5 text-right font-semibold">{{ number_format($it->supply_line_amount) }}원</td>
                        <td class="px-6 py-3.5">@include('portal.partials.fulfillment-status', ['status' => $it->fulfillment_status, 'label' => $it->fulfillment_label])</td>
                        <td class="px-6 py-3.5">
                            <div class="flex justify-end gap-1.5">
                                @foreach (['shipping' => '배송중', 'delivered' => '배송완료'] as $st => $lbl)
                                    @if ($it->fulfillment_status !== $st)
                                        <form method="POST" action="{{ route('portal.supplier.fulfillment.update', $it) }}">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="fulfillment_status" value="{{ $st }}">
                                            <button class="rounded-lg px-3 py-1.5 font-semibold text-xs {{ $st === 'delivered' ? 'bg-emerald-500 text-white hover:bg-emerald-600' : 'bg-sky-100 text-sky-700 hover:bg-sky-200' }}">{{ $lbl }}</button>
                                        </form>
                                    @endif
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-neutral-50 font-black">
                    <td class="px-6 py-4" colspan="3">공급액 합계</td>
                    <td class="px-6 py-4 text-right text-mango-700">{{ number_format($order->items->sum('supply_line_amount')) }}원</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection
