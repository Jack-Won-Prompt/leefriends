<!DOCTYPE html>
<html lang="ko">
<head><meta charset="utf-8"></head>
<body style="margin:0; background:#f3f4f6; font-family:'Apple SD Gothic Neo',Pretendard,sans-serif; color:#1f2937;">
    <div style="max-width:560px; margin:0 auto; padding:24px;">
        <div style="background:#fff; border-radius:16px; overflow:hidden; border:1px solid #eee;">
            <div style="background:#FF9F1C; color:#fff; padding:20px 24px;">
                <div style="font-size:20px; font-weight:800;">🥭 LEEFRIENDS</div>
                <div style="font-size:13px; opacity:.9; margin-top:2px;">세금계산서 발행 확인</div>
            </div>
            <div style="padding:24px;">
                <p style="font-size:15px; margin:0 0 12px;"><b>{{ $storeName }}</b> 매장으로 세금계산서를 발행했습니다.</p>
                <p style="font-size:14px; line-height:1.6; color:#444; margin:0 0 16px;">
                    아래 문서가 국세청 전자세금계산서로 정상 발행되었습니다. 공급받는자(매장)에게는 팝빌을 통해 계산서 메일이 자동 발송됩니다.
                </p>

                @foreach ($docs as $d)
                    <div style="border:1px solid #eee; border-radius:12px; padding:16px; margin-bottom:12px;">
                        <table style="width:100%; border-collapse:collapse; font-size:14px;">
                            <tr>
                                <td style="padding:4px 0; color:#888;">문서구분</td>
                                <td style="padding:4px 0; text-align:right; font-weight:700;">{{ $d['note'] }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0; color:#888;">계산서번호</td>
                                <td style="padding:4px 0; text-align:right; font-weight:700;">{{ $d['invoice_no'] }}</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0; color:#888;">공급가액</td>
                                <td style="padding:4px 0; text-align:right;">{{ number_format($d['supply']) }}원</td>
                            </tr>
                            <tr>
                                <td style="padding:4px 0; color:#888;">부가세</td>
                                <td style="padding:4px 0; text-align:right;">{{ number_format($d['vat']) }}원</td>
                            </tr>
                            <tr style="border-top:1px solid #eee;">
                                <td style="padding:8px 0; color:#444; font-weight:700;">합계</td>
                                <td style="padding:8px 0; text-align:right; font-weight:800; color:#D45A1F; font-size:16px;">{{ number_format($d['total']) }}원</td>
                            </tr>
                        </table>
                        <a href="{{ $d['print_url'] }}" style="display:inline-block; margin-top:12px; background:#FF9F1C; color:#fff; text-decoration:none; font-weight:700; font-size:13px; padding:9px 16px; border-radius:10px;">계산서 보기 · 인쇄</a>
                    </div>
                @endforeach

                @if ($docs->count() > 1)
                    <table style="width:100%; border-collapse:collapse; font-size:14px; margin-top:4px;">
                        <tr style="border-top:2px solid #eee;">
                            <td style="padding:10px 0; color:#444; font-weight:800;">총 합계</td>
                            <td style="padding:10px 0; text-align:right; font-weight:800; color:#D45A1F; font-size:18px;">{{ number_format($grandTotal) }}원</td>
                        </tr>
                    </table>
                @endif
            </div>
        </div>
        <p style="text-align:center; color:#9ca3af; font-size:12px; margin-top:16px;">© {{ date('Y') }} LEEFRIENDS · 주식회사 오다네트웍스</p>
    </div>
</body>
</html>
