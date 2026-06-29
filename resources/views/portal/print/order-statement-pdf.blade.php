<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<style>
    @font-face { font-family: 'nanum'; font-weight: normal; src: url('{{ storage_path('fonts/NanumGothic.ttf') }}') format('truetype'); }
    @font-face { font-family: 'nanum'; font-weight: bold; src: url('{{ storage_path('fonts/NanumGothicBold.ttf') }}') format('truetype'); }
    * { font-family: 'nanum', sans-serif; }
    body { color: #1f2937; font-size: 12px; margin: 0; }
    .title { background: #FF9F1C; color: #fff; padding: 16px 22px; text-align: center; }
    .title h1 { margin: 0; font-size: 22px; letter-spacing: 8px; }
    .title .no { font-size: 12px; opacity: .9; margin-top: 4px; }
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
    .sub td { background: #fafafa; font-weight: bold; }
    .total td { background: #f9fafb; font-weight: bold; font-size: 13px; }
    .totalv { color: #D45A1F; }
    .note { margin-top: 16px; text-align: center; color: #9ca3af; font-size: 10.5px; }
</style>
</head>
<body>
@php
    $store = $order->store;
    $totalQty = $order->items->sum('qty');
@endphp
<div class="title">
    <h1>거 래 명 세 서</h1>
    <div class="no">발주번호 {{ $order->order_no }} · 발주일 {{ $order->created_at->format('Y년 m월 d일') }}</div>
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
                </table>
            </td>
            <td style="width:50%; padding-left:8px;">
                <table class="party box">
                    <tr><td class="head" colspan="2">받는 곳 (매장)</td></tr>
                    <tr><td class="lbl">매장</td><td><b>{{ $store->name ?? '-' }}</b></td></tr>
                    <tr><td class="lbl">연락처</td><td>{{ $store->phone ?? '-' }}</td></tr>
                    <tr><td class="lbl">주소</td><td>{{ $store ? (($store->postcode ? '('.$store->postcode.') ' : '').$store->full_delivery_address) : '-' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:40%;">품목</th>
                <th style="width:14%;">단위</th>
                <th class="r" style="width:12%;">수량</th>
                <th class="r" style="width:17%;">단가</th>
                <th class="r" style="width:17%;">금액</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $it)
                <tr>
                    <td><b>{{ $it->product_name }}</b></td>
                    <td>{{ $it->unit }}</td>
                    <td class="r">{{ number_format($it->qty) }}</td>
                    <td class="r">{{ number_format($it->store_unit_price) }}원</td>
                    <td class="r">{{ number_format($it->store_line_amount) }}원</td>
                </tr>
            @endforeach
            <tr class="sub">
                <td colspan="4" class="r">매장 출고가 합계 (총 {{ number_format($totalQty) }}개)</td>
                <td class="r">{{ number_format($order->store_amount) }}원</td>
            </tr>
            @if ($order->shipping_fee)
                <tr class="sub">
                    <td colspan="4" class="r">택배비 ({{ number_format($order->shipping_box_count) }}박스 × {{ number_format($order->shipping_unit_price) }}원)</td>
                    <td class="r">{{ number_format($order->shipping_fee) }}원</td>
                </tr>
            @endif
            <tr class="total">
                <td colspan="4" class="r">발주 합계</td>
                <td class="r totalv">{{ number_format($order->order_total) }}원</td>
            </tr>
        </tbody>
    </table>

    <div class="note">본 명세서는 매장 구매가(부가세 포함) 기준으로 발행되었습니다. 공급 주체와 무관하게 본사가 공급자입니다. · LEEFRIENDS 발주포털</div>
</div>
</body>
</html>
