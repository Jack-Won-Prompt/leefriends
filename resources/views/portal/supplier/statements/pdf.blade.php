<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<style>
    @font-face { font-family: 'nanum'; font-weight: normal; font-style: normal; src: url('{{ storage_path('fonts/NanumGothic.ttf') }}') format('truetype'); }
    @font-face { font-family: 'nanum'; font-weight: bold; font-style: normal; src: url('{{ storage_path('fonts/NanumGothicBold.ttf') }}') format('truetype'); }
    * { font-family: 'nanum', sans-serif; }
    body { color: #1f2937; font-size: 12px; margin: 0; }
    .title { background: #FF9F1C; color: #fff; padding: 16px 22px; text-align: center; }
    .title h1 { margin: 0; font-size: 22px; letter-spacing: 6px; }
    .title .no { font-size: 12px; color: #fff; opacity: .9; margin-top: 4px; }
    .wrap { padding: 22px; }
    table { width: 100%; border-collapse: collapse; }
    .party td { padding: 6px 10px; font-size: 11.5px; vertical-align: top; }
    .party .box { border: 1px solid #e5e7eb; }
    .party .head { background: #f3f4f6; font-weight: bold; color: #6b7280; font-size: 11px; padding: 6px 10px; }
    .party .lbl { color: #9ca3af; width: 64px; }
    .items { margin-top: 18px; border: 1px solid #e5e7eb; }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    .items th { background: #f3f4f6; color: #6b7280; font-weight: bold; padding: 8px 10px; text-align: left; font-size: 11px; border-bottom: 1px solid #e5e7eb; }
    .items td { padding: 8px 10px; border-bottom: 1px solid #f1f1f1; }
    .r { text-align: right; }
    .tfoot td { background: #f9fafb; font-weight: bold; font-size: 13px; }
    .total { color: #D45A1F; }
    .note { margin-top: 16px; text-align: center; color: #9ca3af; font-size: 10.5px; }
</style>
</head>
<body>
<div class="title">
    <h1>거 래 명 세 서</h1>
    <div class="no">명세서번호 {{ $statement->statement_no }} · 작성일 {{ $statement->created_at->format('Y년 m월 d일') }}</div>
    <div style="margin-top:6px; text-align:center;"><x-barcode :value="$statement->statement_no" format="html" /></div>
</div>

<div class="wrap">
    <table>
        <tr>
            <td style="width:50%; padding-right:8px;">
                <table class="party box">
                    <tr><td class="head" colspan="2">공급자 (공급처)</td></tr>
                    <tr><td class="lbl">상호</td><td><b>{{ $statement->supplier_name }}</b></td></tr>
                    <tr><td class="lbl">등록번호</td><td>{{ optional($statement->supplier)->biz_no ?: '-' }}</td></tr>
                    <tr><td class="lbl">대표자</td><td>{{ optional($statement->supplier)->ceo ?: '-' }}</td></tr>
                </table>
            </td>
            <td style="width:50%; padding-left:8px;">
                <table class="party box">
                    <tr><td class="head" colspan="2">공급받는자 (본사)</td></tr>
                    <tr><td class="lbl">상호</td><td><b>주식회사 오다네트웍스</b></td></tr>
                    <tr><td class="lbl">대표자</td><td>이윤석</td></tr>
                    <tr><td class="lbl">등록번호</td><td>827-81-03115</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>품목</th>
                <th style="width:12%;">단위</th>
                <th class="r" style="width:10%;">수량</th>
                <th class="r" style="width:16%;">공급단가</th>
                <th class="r" style="width:16%;">공급가액</th>
                <th class="r" style="width:14%;">세액</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($statement->items ?? [] as $l)
                <tr>
                    <td><b>{{ $l['name'] ?? '-' }}</b></td>
                    <td>{{ $l['unit'] ?? '' }}</td>
                    <td class="r">{{ number_format($l['qty'] ?? 0) }}</td>
                    <td class="r">{{ number_format($l['unit_price'] ?? 0) }}원</td>
                    <td class="r">{{ number_format($l['supply'] ?? 0) }}원</td>
                    <td class="r">{{ number_format($l['tax'] ?? 0) }}원</td>
                </tr>
            @endforeach
            <tr class="tfoot">
                <td colspan="4" class="r">합계 (공급가액 {{ number_format($statement->supply_total) }}원 + 세액 {{ number_format($statement->vat) }}원)</td>
                <td colspan="2" class="r total">{{ number_format($statement->total) }}원</td>
            </tr>
        </tbody>
    </table>

    <div class="note">본 명세서는 공급가(원가) 기준으로 발행되었습니다. · LEEFRIENDS 발주포털</div>
</div>
</body>
</html>
