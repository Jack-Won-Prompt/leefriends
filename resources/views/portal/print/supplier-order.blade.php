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
    .c { text-align: center; }
    .tfoot td { background: #f9fafb; font-weight: bold; font-size: 13px; }
    .note { margin-top: 16px; text-align: center; color: #9ca3af; font-size: 10.5px; }
</style>
</head>
<body>
<div class="title">
    <h1>발 주 서</h1>
    <div class="no">발주번호 {{ $order->order_no }} · 발주일 {{ $order->created_at->format('Y년 m월 d일') }}</div>
</div>

<div class="wrap">
    <table>
        <tr>
            <td style="width:50%; padding-right:8px;">
                <table class="party box">
                    <tr><td class="head" colspan="2">발주처 (본사)</td></tr>
                    <tr><td class="lbl">상호</td><td><b>주식회사 오다네트웍스</b></td></tr>
                    <tr><td class="lbl">대표자</td><td>이윤석</td></tr>
                    <tr><td class="lbl">등록번호</td><td>827-81-03115</td></tr>
                </table>
            </td>
            <td style="width:50%; padding-left:8px;">
                <table class="party box">
                    <tr><td class="head" colspan="2">공급처 (수신)</td></tr>
                    <tr><td class="lbl">상호</td><td><b>{{ $supplier->name }}</b></td></tr>
                    <tr><td class="lbl">대표자</td><td>{{ $supplier->ceo ?: '-' }}</td></tr>
                    <tr><td class="lbl">등록번호</td><td>{{ $supplier->biz_no ?: '-' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="margin-top:10px;">
        <tr>
            <td style="width:100%;">
                <table class="party box">
                    <tr><td class="head" colspan="2">배송지 (납품처 · 매장)</td></tr>
                    <tr><td class="lbl">매장</td><td><b>{{ optional($order->store)->name ?? '-' }}</b></td></tr>
                    <tr><td class="lbl">주소</td><td>{{ optional($order->store)->full_delivery_address ?: '-' }}</td></tr>
                    <tr><td class="lbl">연락처</td><td>{{ optional($order->store)->phone ?: '-' }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:55%;">품목</th>
                <th style="width:25%;">규격</th>
                <th class="r" style="width:20%;">수량</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $it)
                <tr>
                    <td><b>{{ $it->product_name }}</b></td>
                    <td>{{ $it->unit }}</td>
                    <td class="r">{{ number_format($it->qty) }}</td>
                </tr>
            @endforeach
            <tr class="tfoot">
                <td colspan="2" class="r">합계 수량</td>
                <td class="r">{{ number_format($items->sum('qty')) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($order->note)
        <p style="margin-top:14px; font-size:11.5px;"><b>요청사항:</b> {{ $order->note }}</p>
    @endif
    <div class="note">본 발주서는 수량 기준이며 단가·금액은 표기하지 않습니다. · LEEFRIENDS 발주포털</div>
</div>
</body>
</html>
