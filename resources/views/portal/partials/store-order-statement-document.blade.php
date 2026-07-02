{{-- 발주 거래명세서 문서 (본사 → 매장). $order 필요 --}}
@php
    $store = $order->store;
    $totalQty = $order->items->sum('qty');
    $totalAmount = $order->items->sum('store_line_amount');
@endphp
<div class="bg-white rounded-2xl shadow border border-neutral-200 overflow-hidden" id="invoice-doc">
    <div class="bg-mango-500 text-white px-7 py-5 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-black tracking-wide">거 래 명 세 서</h1>
            <p class="text-white/80 text-sm mt-0.5">발주번호 {{ $order->order_no }}</p>
        </div>
        <div class="text-right text-sm">
            <p @if (! empty($editableDate)) x-text="stmtDateLabel" @endif>{{ ($statementDate ?? $order->created_at)->format('Y년 m월 d일') }}</p>
        </div>
    </div>

    <div class="p-7">
        <div class="grid grid-cols-2 gap-4 mb-6">
            {{-- 공급자 = 본사 --}}
            <div class="rounded-xl border border-neutral-200 overflow-hidden">
                <div class="bg-neutral-100 px-4 py-2 text-xs font-bold text-neutral-500">공급자 (본사)</div>
                <table class="w-full text-sm">
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400 w-20">상호</td><td class="px-4 py-2 font-bold">주식회사 오다네트웍스</td></tr>
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400">대표자</td><td class="px-4 py-2">이윤석</td></tr>
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400">등록번호</td><td class="px-4 py-2">827-81-03115</td></tr>
                    <tr><td class="px-4 py-2 text-neutral-400">주소</td><td class="px-4 py-2 text-neutral-600">경기도 의정부시 천보로 14, 1113호(민락동)</td></tr>
                </table>
            </div>
            {{-- 받는 곳 = 매장 --}}
            <div class="rounded-xl border border-neutral-200 overflow-hidden">
                <div class="bg-neutral-100 px-4 py-2 text-xs font-bold text-neutral-500">받는 곳 (매장)</div>
                <table class="w-full text-sm">
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400 w-20">매장</td><td class="px-4 py-2 font-bold">{{ $store->name ?? '-' }}</td></tr>
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400">대표자</td><td class="px-4 py-2">{{ $store->ceo ?: '-' }}</td></tr>
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400">등록번호</td><td class="px-4 py-2">{{ $store->biz_no ?: '-' }}</td></tr>
                    <tr><td class="px-4 py-2 text-neutral-400">주소</td><td class="px-4 py-2 text-neutral-600">{{ $store ? ($store->postcode ? '('.$store->postcode.') ' : '').$store->full_delivery_address : '-' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-neutral-100 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-4 py-2.5">품목</th>
                        <th class="text-left font-semibold px-4 py-2.5">공급</th>
                        <th class="text-left font-semibold px-4 py-2.5">단위</th>
                        <th class="text-right font-semibold px-4 py-2.5">수량</th>
                        <th class="text-right font-semibold px-4 py-2.5">단가</th>
                        <th class="text-right font-semibold px-4 py-2.5">금액</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($order->items as $it)
                        <tr>
                            <td class="px-4 py-2.5 font-semibold text-neutral-800">{{ $it->product_name }}</td>
                            <td class="px-4 py-2.5 text-neutral-500">{{ $it->supply_type === 'supplier' ? ($it->supplier_name ?? '공급처') : '본사' }}</td>
                            <td class="px-4 py-2.5 text-neutral-500">{{ $it->unit }}</td>
                            <td class="px-4 py-2.5 text-right">{{ number_format($it->qty) }}</td>
                            <td class="px-4 py-2.5 text-right">{{ number_format($it->store_unit_price) }}원</td>
                            <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($it->store_line_amount) }}원</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-neutral-50 font-bold">
                        <td class="px-4 py-3" colspan="5">매장 출고가 합계 (총 {{ number_format($totalQty) }}개)</td>
                        <td class="px-4 py-3 text-right">{{ number_format($order->store_amount) }}원</td>
                    </tr>
                    @if ($order->shipping_fee)
                        <tr class="bg-neutral-50 font-bold">
                            <td class="px-4 py-3" colspan="5">택배비 ({{ number_format($order->shipping_box_count) }}박스 × {{ number_format($order->shipping_unit_price) }}원)</td>
                            <td class="px-4 py-3 text-right">{{ number_format($order->shipping_fee) }}원</td>
                        </tr>
                    @endif
                    <tr class="bg-neutral-50 font-black border-t border-neutral-200">
                        <td class="px-4 py-3" colspan="5">발주 합계</td>
                        <td class="px-4 py-3 text-right text-mango-700">{{ number_format($order->order_total) }}원</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    @page { margin: 0; }   /* 브라우저 기본 머리글/바닥글(URL·날짜) 제거 */
    body * { visibility: hidden; }
    #invoice-doc, #invoice-doc * { visibility: visible; }
    #invoice-doc { position: absolute; left: 0; top: 0; width: 100%; padding: 12mm; }
}
</style>
