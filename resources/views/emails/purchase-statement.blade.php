<!DOCTYPE html>
<html lang="ko">
<head><meta charset="utf-8"></head>
<body style="margin:0; background:#f5f5f5; font-family:'Apple SD Gothic Neo',sans-serif;">
    <div style="max-width:560px; margin:0 auto; padding:24px;">
        <div style="background:#fff; border-radius:16px; padding:28px;">
            <h1 style="margin:0 0 6px; font-size:20px; color:#1f2937;">🧾 구매 거래명세서</h1>
            <p style="margin:0 0 18px; color:#666; font-size:14px; line-height:1.6;">
                {{ $po->supplier_name }} 담당자님, 본사에서 구매발주 «{{ $po->po_no }}»에 대한 거래명세서를 보내드립니다. 첨부된 PDF를 확인해 주세요.
            </p>
            @php $tax = \App\Support\TaxSummary::fromLines($po->items->map(fn ($i) => ['amount' => (int) $i->line_amount, 'tax_type' => $i->supplyProduct->tax_type ?? 'exc'])->all()); @endphp
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr><td style="padding:6px 0; color:#888;">발주번호</td><td style="padding:6px 0; text-align:right; font-weight:700;">{{ $po->po_no }}</td></tr>
                <tr><td style="padding:6px 0; color:#888;">과세 공급가액</td><td style="padding:6px 0; text-align:right; font-weight:700;">{{ number_format($tax['taxable']) }}원</td></tr>
                <tr><td style="padding:6px 0; color:#888;">부가세 (VAT)</td><td style="padding:6px 0; text-align:right; font-weight:700;">{{ number_format($tax['vat']) }}원</td></tr>
                @if ($tax['exempt'] > 0)
                    <tr><td style="padding:6px 0; color:#888;">면세 공급가액</td><td style="padding:6px 0; text-align:right; font-weight:700;">{{ number_format($tax['exempt']) }}원</td></tr>
                @endif
                <tr style="border-top:1px solid #eee;"><td style="padding:10px 0; color:#444; font-weight:700;">합계 (부가세 포함)</td><td style="padding:10px 0; text-align:right; font-weight:800; color:#D45A1F; font-size:18px;">{{ number_format($tax['total']) }}원</td></tr>
            </table>
        </div>
        <p style="text-align:center; color:#aaa; font-size:12px; margin-top:16px;">망고정 발주포털</p>
    </div>
</body>
</html>
