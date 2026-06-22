<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<style>
    @font-face { font-family: 'nanum'; font-weight: normal; font-style: normal; src: url('{{ storage_path('fonts/NanumGothic.ttf') }}') format('truetype'); }
    @font-face { font-family: 'nanum'; font-weight: bold; font-style: normal; src: url('{{ storage_path('fonts/NanumGothicBold.ttf') }}') format('truetype'); }
    * { font-family: 'nanum', sans-serif; }
    body { color: #1f2937; font-size: 12px; margin: 0; }
    .title { background: #FF9F1C; color: #fff; padding: 16px 22px; }
    .title h1 { margin: 0; font-size: 22px; letter-spacing: 6px; }
    .title .no { font-size: 12px; color: #fff; opacity: .9; margin-top: 4px; }
    .wrap { padding: 22px; }
    table { width: 100%; border-collapse: collapse; }
    .party td { padding: 6px 10px; font-size: 11.5px; vertical-align: top; }
    .party .box { border: 1px solid #e5e7eb; }
    .party .head { background: #f3f4f6; font-weight: bold; color: #6b7280; font-size: 11px; padding: 6px 10px; }
    .party .lbl { color: #9ca3af; width: 64px; }
    .items { margin-top: 18px; border: 1px solid #e5e7eb; }
    .items th { background: #f3f4f6; color: #6b7280; font-weight: bold; padding: 8px 10px; text-align: left; font-size: 11px; border-bottom: 1px solid #e5e7eb; }
    .items td { padding: 8px 10px; border-bottom: 1px solid #f1f1f1; }
    .r { text-align: right; }
    .c { text-align: center; }
    .tfoot td { background: #f9fafb; font-weight: bold; font-size: 13px; }
    .total { color: #D45A1F; }
    .note { margin-top: 16px; text-align: center; color: #9ca3af; font-size: 10.5px; }
</style>
</head>
<body>
@php $store = $store; @endphp
<div class="title">
    <h1>거 래 명 세 서</h1>
    <div class="no">발행일 {{ $date->format('Y년 m월 d일') }}</div>
</div>

<div class="wrap">
    <table>
        <tr>
            <td style="width:50%; padding-right:8px;">
                <table class="party box">
                    <tr><td class="head" colspan="2">공급자 (본사)</td></tr>
                    <tr><td class="lbl">상호</td><td><b>주식회사 오다네트웍스</b></td></tr>
                    <tr><td class="lbl">대표자</td><td>이윤석</td></tr>
                    <tr><td class="lbl">등록번호</td><td>827-81-03115</td></tr>
                    <tr><td class="lbl">주소</td><td>경기도 의정부시 천보로 14, 1113호(민락동)</td></tr>
                </table>
            </td>
            <td style="width:50%; padding-left:8px;">
                <table class="party box">
                    <tr><td class="head" colspan="2">공급받는자 (매장)</td></tr>
                    <tr><td class="lbl">매장</td><td><b>{{ $store->name }}</b></td></tr>
                    <tr><td class="lbl">지역</td><td>{{ $store->region ?: '-' }}</td></tr>
                    <tr><td class="lbl">주소</td><td>{{ ($store->postcode ? '('.$store->postcode.') ' : '').trim(($store->address ?? '').' '.($store->address_detail ?? '')) ?: '-' }}</td></tr>
                    <tr><td class="lbl">이메일</td><td>{{ $store->email ?: '-' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:14%;">코드</th>
                <th>품목</th>
                <th style="width:12%;">단위</th>
                <th class="r" style="width:10%;">수량</th>
                <th class="r" style="width:16%;">단가</th>
                <th class="r" style="width:18%;">금액</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $l)
                <tr>
                    <td>{{ $l['code'] }}</td>
                    <td><b>{{ $l['name'] }}</b></td>
                    <td>{{ $l['unit'] }}</td>
                    <td class="r">{{ number_format($l['qty']) }}</td>
                    <td class="r">{{ number_format($l['price']) }}원</td>
                    <td class="r">{{ number_format($l['amount']) }}원</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="tfoot">
                <td colspan="5" class="r">합계 (총 {{ number_format(collect($lines)->sum('qty')) }}개)</td>
                <td class="r total">{{ number_format($total) }}원</td>
            </tr>
        </tfoot>
    </table>

    <div class="note">본 명세서는 매장 구매가(부가세 포함) 기준으로 발행되었습니다. · LEEFRIENDS 발주포털</div>
</div>
</body>
</html>
