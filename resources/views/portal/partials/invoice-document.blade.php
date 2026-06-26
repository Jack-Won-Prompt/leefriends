{{-- 세금계산서 문서 (공급처 → 본사). $invoice 필요 --}}
<div class="bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden print:shadow-none print:border-0" id="invoice-doc">
    <div class="bg-mango-500 text-white px-7 py-5 flex items-center justify-between print:bg-mango-500">
        <div>
            <h2 class="text-xl font-black tracking-wide">세 금 계 산 서</h2>
            <p class="text-white/80 text-sm mt-0.5">{{ $invoice->status_label }} · 공급자 보관용</p>
        </div>
        <div class="text-right text-sm">
            <p class="font-bold">{{ $invoice->invoice_no }}</p>
            <p class="text-white/80">작성일자 {{ $invoice->issue_date?->format('Y년 m월 d일') }}</p>
        </div>
    </div>

    <div class="p-7">
        <div class="grid md:grid-cols-2 gap-4 mb-6">
            {{-- 공급자 = 공급처 --}}
            <div class="rounded-xl border border-neutral-200 overflow-hidden">
                <div class="bg-neutral-100 px-4 py-2 text-xs font-bold text-neutral-500">공 급 자 (공급처)</div>
                <table class="w-full text-sm">
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400 w-24">등록번호</td><td class="px-4 py-2 font-semibold">{{ $invoice->supplier->biz_no ?? '-' }}</td></tr>
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400">상호</td><td class="px-4 py-2 font-bold">{{ $invoice->supplier->name ?? '-' }}</td></tr>
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400">대표자</td><td class="px-4 py-2">{{ $invoice->supplier->ceo ?? '-' }}</td></tr>
                    <tr><td class="px-4 py-2 text-neutral-400">주소</td><td class="px-4 py-2 text-neutral-600">{{ $invoice->supplier->address ?? '-' }}</td></tr>
                </table>
            </div>
            {{-- 공급받는자 = 본사 --}}
            <div class="rounded-xl border border-neutral-200 overflow-hidden">
                <div class="bg-neutral-100 px-4 py-2 text-xs font-bold text-neutral-500">공 급 받 는 자 (본사)</div>
                <table class="w-full text-sm">
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400 w-24">등록번호</td><td class="px-4 py-2 font-semibold">827-81-03115</td></tr>
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400">상호</td><td class="px-4 py-2 font-bold">주식회사 오다네트웍스</td></tr>
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400">대표자</td><td class="px-4 py-2">이윤석</td></tr>
                    <tr><td class="px-4 py-2 text-neutral-400">주소</td><td class="px-4 py-2 text-neutral-600">경기도 의정부시 천보로 14, 1113호(민락동)</td></tr>
                </table>
            </div>
        </div>

        {{-- 금액 요약 --}}
        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="rounded-xl bg-neutral-50 p-4 text-center">
                <p class="text-xs text-neutral-500 font-semibold">공급가액</p>
                <p class="text-lg font-black text-neutral-900 mt-1">{{ number_format($invoice->supply_amount) }}원</p>
            </div>
            <div class="rounded-xl bg-neutral-50 p-4 text-center">
                <p class="text-xs text-neutral-500 font-semibold">세액 (부가세)</p>
                <p class="text-lg font-black text-neutral-900 mt-1">{{ number_format($invoice->vat) }}원</p>
            </div>
            <div class="rounded-xl bg-mango-500 text-white p-4 text-center">
                <p class="text-xs text-white/80 font-semibold">합계금액</p>
                <p class="text-lg font-black mt-1">{{ number_format($invoice->total_amount) }}원</p>
            </div>
        </div>

        {{-- 품목 명세 --}}
        <div class="rounded-xl border border-neutral-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-neutral-100 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-4 py-2.5">품목 (매장)</th>
                        <th class="text-right font-semibold px-4 py-2.5">공급단가</th>
                        <th class="text-right font-semibold px-4 py-2.5">수량</th>
                        <th class="text-right font-semibold px-4 py-2.5">공급가액</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($invoice->items as $it)
                        <tr>
                            <td class="px-4 py-2.5">
                                <span class="font-semibold text-neutral-800">{{ $it->product_name }}</span>
                                <span class="text-xs text-neutral-400 ml-1">· {{ $it->order->store->name ?? '' }} ({{ $it->order->order_no ?? '' }})</span>
                            </td>
                            <td class="px-4 py-2.5 text-right">{{ number_format($it->supply_unit_price) }}원</td>
                            <td class="px-4 py-2.5 text-right">{{ number_format($it->qty) }}{{ $it->unit }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($it->supply_line_amount) }}원</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-neutral-50 font-black">
                        <td class="px-4 py-3" colspan="3">공급가액 합계</td>
                        <td class="px-4 py-3 text-right text-mango-700">{{ number_format($invoice->supply_amount) }}원</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        @if ($invoice->note)
            <p class="mt-4 text-sm text-neutral-500">비고: {{ $invoice->note }}</p>
        @endif
    </div>
</div>

<style>
@media print {
    @page { margin: 0; }
    body * { visibility: hidden; }
    #invoice-doc, #invoice-doc * { visibility: visible; }
    #invoice-doc { position: absolute; left: 0; top: 0; width: 100%; padding: 12mm; }
}
</style>
