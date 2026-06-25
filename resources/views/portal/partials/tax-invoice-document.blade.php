{{-- 전자세금계산서/계산서 문서 (스냅샷 기반, 양방향 공용). $invoice 필요 --}}
@php($isExempt = str_contains($invoice->note ?? '', '면세'))
<div class="bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden print:shadow-none print:border-0" id="invoice-doc">
    <div class="px-7 py-5 flex items-center justify-between text-white {{ $isExempt ? 'bg-sky-600' : 'bg-mango-500' }}">
        <div>
            <h2 class="text-xl font-black tracking-wide">{{ $isExempt ? '계 산 서 (면세)' : '세 금 계 산 서' }}</h2>
            <p class="text-white/80 text-sm mt-0.5">
                {{ $invoice->status_label }}
                @if ($invoice->nts_confirm_num) · 승인번호 {{ $invoice->nts_confirm_num }} @endif
            </p>
        </div>
        <div class="text-right text-sm">
            <p class="font-bold">{{ $invoice->invoice_no }}</p>
            <p class="text-white/80">작성일자 {{ $invoice->issue_date?->format('Y년 m월 d일') }}</p>
        </div>
    </div>

    <div class="p-7">
        <div class="grid md:grid-cols-2 gap-4 mb-6">
            {{-- 공급자 --}}
            <div class="rounded-xl border border-neutral-200 overflow-hidden">
                <div class="bg-neutral-100 px-4 py-2 text-xs font-bold text-neutral-500">공 급 자</div>
                <table class="w-full text-sm">
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400 w-24">등록번호</td><td class="px-4 py-2 font-semibold">{{ $invoice->invoicer_corp_num ?: '-' }}</td></tr>
                    <tr><td class="px-4 py-2 text-neutral-400">상호</td><td class="px-4 py-2 font-bold">{{ $invoice->invoicer_corp_name ?: '-' }}</td></tr>
                </table>
            </div>
            {{-- 공급받는자 --}}
            <div class="rounded-xl border border-neutral-200 overflow-hidden">
                <div class="bg-neutral-100 px-4 py-2 text-xs font-bold text-neutral-500">공 급 받 는 자</div>
                <table class="w-full text-sm">
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400 w-24">등록번호</td><td class="px-4 py-2 font-semibold">{{ $invoice->invoicee_corp_num ?: '-' }}</td></tr>
                    <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400">상호</td><td class="px-4 py-2 font-bold">{{ $invoice->invoicee_corp_name ?: '-' }}</td></tr>
                    <tr><td class="px-4 py-2 text-neutral-400">이메일</td><td class="px-4 py-2 text-neutral-600">{{ $invoice->invoicee_email ?: '-' }}</td></tr>
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
            <div class="rounded-xl text-white p-4 text-center {{ $isExempt ? 'bg-sky-600' : 'bg-mango-500' }}">
                <p class="text-xs text-white/80 font-semibold">합계금액</p>
                <p class="text-lg font-black mt-1">{{ number_format($invoice->total_amount) }}원</p>
            </div>
        </div>

        {{-- 품목 명세 (line_items 스냅샷) --}}
        <div class="rounded-xl border border-neutral-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-neutral-100 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-4 py-2.5">품목</th>
                        <th class="text-right font-semibold px-4 py-2.5">단가</th>
                        <th class="text-right font-semibold px-4 py-2.5">수량</th>
                        <th class="text-right font-semibold px-4 py-2.5">공급가액</th>
                        <th class="text-right font-semibold px-4 py-2.5">세액</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($invoice->line_items ?? [] as $l)
                        <tr>
                            <td class="px-4 py-2.5">
                                <span class="font-semibold text-neutral-800">{{ $l['name'] ?? '-' }}</span>
                                @if (! empty($l['order_no']))
                                    <span class="text-xs text-neutral-400 ml-1">· {{ $l['order_no'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-right">{{ number_format($l['unit_price'] ?? 0) }}원</td>
                            <td class="px-4 py-2.5 text-right">{{ number_format($l['qty'] ?? 0) }}{{ $l['spec'] ?? '' }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($l['supply'] ?? 0) }}원</td>
                            <td class="px-4 py-2.5 text-right text-neutral-500">{{ number_format($l['tax'] ?? 0) }}원</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-neutral-50 font-black">
                        <td class="px-4 py-3" colspan="3">합계</td>
                        <td class="px-4 py-3 text-right text-mango-700">{{ number_format($invoice->supply_amount) }}원</td>
                        <td class="px-4 py-3 text-right text-neutral-600">{{ number_format($invoice->vat) }}원</td>
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
    body * { visibility: hidden; }
    #invoice-doc, #invoice-doc * { visibility: visible; }
    #invoice-doc { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>
