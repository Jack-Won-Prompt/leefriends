{{-- 본사 거래명세서 문서 (본사 → 매장). $statement 필요 --}}
<div class="bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden" id="invoice-doc">
    <div class="bg-mango-500 text-white px-7 py-5 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black tracking-wide">거 래 명 세 서</h2>
            <p class="text-white/80 text-sm mt-0.5">공급자: 주식회사 오다네트웍스(본사) · 공급받는자: {{ $statement->store_name }}</p>
        </div>
        <div class="text-right text-sm">
            <p class="text-white/80">발송일 {{ $statement->sent_at?->format('Y년 m월 d일 H:i') }}</p>
            <p class="text-white/80">{{ $statement->email }}</p>
        </div>
    </div>

    <div class="p-7">
        <div class="rounded-xl border border-neutral-200 overflow-hidden mb-4">
            <table class="w-full text-sm">
                <thead class="bg-neutral-100 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-4 py-2.5">품목</th>
                        <th class="text-right font-semibold px-4 py-2.5">단가</th>
                        <th class="text-right font-semibold px-4 py-2.5">수량</th>
                        <th class="text-right font-semibold px-4 py-2.5">금액</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($statement->items ?? [] as $l)
                        <tr>
                            <td class="px-4 py-2.5">
                                <span class="font-semibold text-neutral-800">{{ $l['name'] ?? '-' }}</span>
                                @if (! empty($l['code']))<span class="text-xs text-neutral-400 ml-1">{{ $l['code'] }}</span>@endif
                            </td>
                            <td class="px-4 py-2.5 text-right">{{ number_format($l['price'] ?? 0) }}원</td>
                            <td class="px-4 py-2.5 text-right">{{ number_format($l['qty'] ?? 0) }}{{ $l['unit'] ?? '' }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($l['amount'] ?? 0) }}원</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-neutral-50 font-black">
                        <td class="px-4 py-3" colspan="3">합계금액</td>
                        <td class="px-4 py-3 text-right text-mango-700">{{ number_format($statement->total) }}원</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @if ($statement->tax_invoice_id)
            <p class="text-sm text-emerald-600 font-semibold">✓ 세금계산서 발행완료 @if (optional($statement->taxInvoice)->invoice_no)· {{ $statement->taxInvoice->invoice_no }}@endif</p>
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
