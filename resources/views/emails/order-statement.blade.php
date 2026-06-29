<!DOCTYPE html>
<html lang="ko">
<head><meta charset="utf-8"></head>
<body style="margin:0; background:#f3f4f6; font-family:'Apple SD Gothic Neo',Pretendard,sans-serif; color:#1f2937;">
    <div style="max-width:560px; margin:0 auto; padding:24px;">
        <div style="background:#fff; border-radius:16px; overflow:hidden; border:1px solid #eee;">
            <div style="background:#FF9F1C; color:#fff; padding:20px 24px;">
                <div style="font-size:20px; font-weight:800;">🥭 LEEFRIENDS</div>
                <div style="font-size:13px; opacity:.9; margin-top:2px;">거래명세서</div>
            </div>
            <div style="padding:24px;">
                <p style="font-size:15px; margin:0 0 12px;"><b>{{ $order->store->name ?? '매장' }}</b> 점주님께,</p>
                <p style="font-size:14px; line-height:1.6; color:#444; margin:0 0 16px;">
                    발주 «{{ $order->order_no }}»에 대한 거래명세서를 보내드립니다. 첨부된 PDF를 확인해 주세요.
                </p>
                <table style="width:100%; border-collapse:collapse; font-size:14px;">
                    <tr><td style="padding:6px 0; color:#888;">발주번호</td><td style="padding:6px 0; text-align:right; font-weight:700;">{{ $order->order_no }}</td></tr>
                    <tr><td style="padding:6px 0; color:#888;">매장 출고가 합계</td><td style="padding:6px 0; text-align:right; font-weight:700;">{{ number_format($order->store_amount) }}원</td></tr>
                    @if ($order->shipping_fee)
                        <tr><td style="padding:6px 0; color:#888;">택배비</td><td style="padding:6px 0; text-align:right; font-weight:700;">{{ number_format($order->shipping_fee) }}원</td></tr>
                    @endif
                    <tr style="border-top:1px solid #eee;"><td style="padding:10px 0; color:#444; font-weight:700;">발주 합계</td><td style="padding:10px 0; text-align:right; font-weight:800; color:#D45A1F; font-size:18px;">{{ number_format($order->order_total) }}원</td></tr>
                </table>
            </div>
        </div>
        <p style="text-align:center; color:#9ca3af; font-size:12px; margin-top:16px;">© {{ date('Y') }} LEEFRIENDS · 주식회사 오다네트웍스</p>
    </div>
</body>
</html>
