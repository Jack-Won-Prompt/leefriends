<!DOCTYPE html>
<html lang="ko">
<head><meta charset="utf-8"></head>
<body style="margin:0; background:#f3f4f6; font-family:'Apple SD Gothic Neo',Pretendard,sans-serif; color:#1f2937;">
    <div style="max-width:560px; margin:0 auto; padding:24px;">
        <div style="background:#fff; border-radius:16px; overflow:hidden; border:1px solid #eee;">
            <div style="background:#FF9F1C; color:#fff; padding:20px 24px;">
                <div style="font-size:20px; font-weight:800;">🥭 LEEFRIENDS</div>
                <div style="font-size:13px; opacity:.9; margin-top:2px;">공급처 거래명세서</div>
            </div>
            <div style="padding:24px;">
                <p style="font-size:15px; margin:0 0 12px;"><b>{{ $statement->supplier_name }}</b> 공급처의 거래명세서입니다.</p>
                <p style="font-size:14px; line-height:1.6; color:#444; margin:0 0 16px;">
                    공급처에서 거래명세서를 발행하였습니다. 첨부된 PDF 파일을 확인해 주세요.
                </p>
                <table style="width:100%; border-collapse:collapse; font-size:14px;">
                    <tr><td style="padding:6px 0; color:#888;">명세서번호</td><td style="padding:6px 0; text-align:right; font-weight:700;">{{ $statement->statement_no }}</td></tr>
                    <tr><td style="padding:6px 0; color:#888;">품목 수</td><td style="padding:6px 0; text-align:right; font-weight:700;">{{ number_format($statement->item_count) }}건</td></tr>
                    <tr><td style="padding:6px 0; color:#888;">공급가액</td><td style="padding:6px 0; text-align:right; font-weight:700;">{{ number_format($statement->supply_total) }}원</td></tr>
                    <tr style="border-top:1px solid #eee;"><td style="padding:10px 0; color:#444; font-weight:700;">합계 금액</td><td style="padding:10px 0; text-align:right; font-weight:800; color:#D45A1F; font-size:18px;">{{ number_format($statement->total) }}원</td></tr>
                </table>
                <p style="font-size:12px; color:#9ca3af; margin:20px 0 0;">※ 본 명세서는 공급가(원가) 기준입니다.</p>
            </div>
        </div>
        <p style="text-align:center; color:#9ca3af; font-size:12px; margin-top:16px;">© {{ date('Y') }} LEEFRIENDS · 주식회사 오다네트웍스</p>
    </div>
</body>
</html>
