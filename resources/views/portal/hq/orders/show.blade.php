@extends('portal.layout')
@section('title', '발주 상세')

@section('content')
<a href="{{ route('portal.hq.orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600 mb-5">← 발주 관리</a>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 rounded-2xl bg-white shadow-sm border border-neutral-100 p-7">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black text-neutral-900">{{ $order->order_no }}</h2>
                <p class="text-sm text-neutral-400 mt-1">{{ $order->created_at->format('Y년 m월 d일 H:i') }}</p>
            </div>
            @include('portal.partials.order-status', ['status' => $order->status, 'label' => $order->status_label])
        </div>
        <dl class="grid sm:grid-cols-2 gap-x-8 gap-y-3 mt-6 text-sm">
            <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">발주 매장</dt><dd class="font-bold">{{ $order->store->name ?? '-' }}</dd></div>
            <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">발주자</dt><dd>{{ $order->user->name ?? '-' }}</dd></div>
            <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">배송지</dt><dd class="text-right">{{ $order->store ? ($order->store->postcode ? '('.$order->store->postcode.') ' : '').$order->store->full_delivery_address : '-' }}</dd></div>
            <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">연락처</dt><dd>{{ $order->store->phone ?? '-' }}</dd></div>
        </dl>
        @if ($order->note)
            <p class="mt-4 text-sm text-neutral-600 bg-neutral-50 rounded-xl p-4">📝 {{ $order->note }}</p>
        @endif
    </div>

    <div class="rounded-2xl bg-neutral-900 text-white p-7">
        <h3 class="font-bold text-white/70 text-sm mb-4">정산 요약</h3>
        <div class="flex justify-between py-2 border-b border-white/10"><span class="text-white/70">매장 출고가 합계</span><span class="font-bold">{{ number_format($order->store_amount) }}원</span></div>
        <div class="flex justify-between py-2 border-b border-white/10"><span class="text-white/70">공급가(원가) 합계</span><span class="font-bold">{{ number_format($order->supply_amount) }}원</span></div>
        <div class="flex justify-between py-3 mt-1"><span class="text-mango-300 font-bold">본사 마진</span><span class="text-mango-300 font-black text-lg">{{ number_format($order->store_amount - $order->supply_amount) }}원</span></div>

        @php($taxInvoice = \App\Models\TaxInvoice::where('direction', 'hq_to_store')->where('order_id', $order->id)->where('status', 'issued')->latest()->first())
        <div class="mt-5 pt-4 border-t border-white/10">
            @if ($taxInvoice)
                <div class="rounded-xl bg-emerald-500/15 border border-emerald-400/30 px-4 py-3 text-sm">
                    <p class="font-bold text-emerald-300">✓ 세금계산서 발행 완료</p>
                    <p class="text-white/70 mt-1">계산서번호 {{ $taxInvoice->invoice_no }} · 합계 {{ number_format($taxInvoice->total_amount) }}원</p>
                    <p class="text-white/50 text-xs mt-0.5">{{ $taxInvoice->invoicee_corp_name }} ({{ $taxInvoice->invoicee_email }})</p>
                </div>
            @else
                <form method="POST" action="{{ route('portal.hq.tax_invoices.issue', $order) }}"
                      onsubmit="return confirm('본사 → 매장 세금계산서를 발행합니다.\n수신: {{ $order->store->name }} ({{ $order->store->email }})\n진행하시겠습니까?')">
                    @csrf
                    <button type="submit"
                            class="w-full rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-3 text-sm transition">
                        🧾 세금계산서 발행 (본사 → 매장)
                    </button>
                    @unless ($order->store?->biz_no)
                        <p class="text-amber-300/80 text-xs mt-2">⚠ 매장 사업자등록번호가 없습니다. 매장 관리에서 먼저 등록하세요.</p>
                    @endunless
                </form>
            @endif
        </div>
    </div>
</div>

{{-- 품목: 공급처명 확인 가능 --}}
<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">발주 품목 · 공급처 / 배송현황</div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">품목</th>
                    <th class="text-left font-semibold px-6 py-3">공급 구분 / 공급처</th>
                    <th class="text-right font-semibold px-6 py-3">공급가</th>
                    <th class="text-right font-semibold px-6 py-3">출고가</th>
                    <th class="text-right font-semibold px-6 py-3">수량</th>
                    <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">공급액</th>
                    <th class="text-left font-semibold px-6 py-3">배송</th>
                    <th class="text-right font-semibold px-6 py-3 w-44">본사 직공급 처리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($order->items as $it)
                    <tr>
                        <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $it->product_name }}</td>
                        <td class="px-6 py-3.5">
                            @if ($it->supply_type === 'supplier')
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-sky-100 text-sky-700">공급처</span>
                                <span class="font-semibold text-neutral-800 ml-1">{{ $it->supplier_name }}</span>
                            @else
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-mango-100 text-mango-700">본사 직공급</span>
                            @endif
                        </td>
                        <td class="px-6 py-3.5 text-right text-neutral-500">{{ $it->supply_type === 'supplier' ? number_format($it->supply_unit_price).'원' : '-' }}</td>
                        <td class="px-6 py-3.5 text-right">{{ number_format($it->store_unit_price) }}원</td>
                        <td class="px-6 py-3.5 text-right">{{ number_format($it->qty) }}{{ $it->unit }}</td>
                        <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ $it->supply_type === 'supplier' ? number_format($it->supply_line_amount).'원' : '-' }}</td>
                        <td class="px-6 py-3.5">@include('portal.partials.fulfillment-status', ['status' => $it->fulfillment_status, 'label' => $it->fulfillment_label])</td>
                        <td class="px-6 py-3.5">
                            @if ($it->supply_type === 'hq')
                                <div class="flex justify-end gap-1.5">
                                    @foreach (['shipping' => '배송중', 'delivered' => '완료'] as $st => $lbl)
                                        @if ($it->fulfillment_status !== $st)
                                            <form method="POST" action="{{ route('portal.hq.orders.items.update', [$order, $it]) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="fulfillment_status" value="{{ $st }}">
                                                <button class="rounded-lg px-3 py-1.5 font-semibold text-xs {{ $st === 'delivered' ? 'bg-emerald-500 text-white hover:bg-emerald-600' : 'bg-sky-100 text-sky-700 hover:bg-sky-200' }}">{{ $lbl }}</button>
                                            </form>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <span class="block text-right text-xs text-neutral-400">공급처 직배송</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
