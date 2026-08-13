@extends('portal.layout')
@section('title', '주문 상세')

@section('content')
<a href="{{ route('portal.supplier.orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600 mb-5">← 주문 관리</a>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-4 mb-3">
    <div class="flex flex-wrap items-center gap-2">
        <h2 class="text-xl font-black text-neutral-900">{{ $order->order_no }}</h2>
        <span class="text-xs text-neutral-400">{{ $order->created_at->format('Y년 m월 d일 H:i') }}</span>
        @include('portal.partials.order-status', ['status' => $order->status, 'label' => $order->status_label])
    </div>
    {{-- 정보 한 줄 (넓은 박스 대신) --}}
    <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm mt-3 pt-3 border-t border-neutral-100">
        <span class="inline-flex gap-1.5"><span class="text-neutral-500">배송지 (매장)</span><b>{{ $order->store->name ?? '-' }}</b></span>
        <span class="inline-flex gap-1.5"><span class="text-neutral-500">연락처</span>{{ $order->store->phone ?? '-' }}</span>
        <span class="inline-flex gap-1.5 min-w-0"><span class="text-neutral-500 shrink-0">주소</span><span class="truncate" title="{{ $order->store->address ?? '-' }}">{{ $order->store->address ?? '-' }}</span></span>
    </div>
    @if ($order->note)
        <p class="mt-2 text-sm text-neutral-600 bg-neutral-50 rounded-xl px-3 py-2">📝 {{ $order->note }}</p>
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
