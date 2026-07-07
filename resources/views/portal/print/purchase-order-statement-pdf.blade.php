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
@php $sup = $po->supplier; @endphp
<div class="title">
    <h1>거 래 명 세 서</h1>
    <div class="no">구매발주 {{ $po->po_no }} · 발행일 {{ ($statementDate ?? $po->created_at ?? now())->format('Y년 m월 d일') }}</div>
</div>

<div class="wrap">
    <table>
        <tr>
            <td style="width:50%; padding-right:8px;">
                <table class="party box">
                    <tr><td class="head" colspan="2">공급자 (공급처)</td></tr>
                    <tr><td class="lbl">상호</td><td><b>{{ $po->supplier_name }}</b></td></tr>
                    <tr><td class="lbl">대표자</td><td>{{ $sup->ceo ?: '-' }}</td></tr>
                    <tr><td class="lbl">등록번호</td><td>{{ $sup->biz_no ?: '-' }}</td></tr>
                    <tr><td class="lbl">주소</td><td>{{ $sup ? (($sup->postcode ? '('.$sup->postcode.') ' : '').trim(($sup->address ?? '').' '.($sup->address_detail ?? '')) ?: '-') : '-' }}</td></tr>
                </table>
            </td>
            <td style="width:50%; padding-left:8px;">
                <table class="party box">
                    <tr><td class="head" colspan="2">공급받는자 (본사)</td></tr>
                    <tr><td class="lbl">상호</td><td><b>주식회사 오다네트웍스</b></td></tr>
                    <tr><td class="lbl">대표자</td><td>이윤석</td></tr>
                    <tr><td class="lbl">등록번호</td><td>827-81-03115</td></tr>
                    <tr><td class="lbl">주소</td><td>경기도 의정부시 천보로 14, 1113호(민락동)</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width:34%;">품목</th>
                <th style="width:12%;">단위</th>
                <th class="r" style="width:10%;">수량</th>
                <th class="r" style="width:15%;">단가</th>
                <th class="r" style="width:15%;">공급가액</th>
                <th class="r" style="width:14%;">부가세</th>
            </tr>
        </thead>
        <tbody>
            @php $tax = \App\Support\TaxSummary::fromLines($po->items->map(fn ($i) => ['amount' => (int) $i->line_amount, 'tax_type' => $i->supplyProduct->tax_type ?? 'exc'])->all()); @endphp
            @foreach ($po->items as $it)
                @php $tt = $it->supplyProduct->tax_type ?? 'exc'; [$s, $lt] = \App\Models\SupplyProduct::taxBreakdown($tt, (int) $it->line_amount); @endphp
                <tr>
                    <td><b>{{ $it->product_name }}</b>@if ($tt === 'exempt') <span style="font-size:9px; color:#0369a1;">(면세)</span>@endif</td>
                    <td>{{ $it->unit }}</td>
                    <td class="r">{{ number_format($it->qty) }}</td>
                    <td class="r">{{ number_format($it->unit_price) }}원</td>
                    <td class="r">{{ number_format($s) }}원</td>
                    <td class="r">{{ $tt === 'exempt' ? '면세' : number_format($lt).'원' }}</td>
                </tr>
            @endforeach
            <tr class="sub"><td colspan="5" class="r">과세 공급가액</td><td class="r">{{ number_format($tax['taxable']) }}원</td></tr>
            <tr class="sub"><td colspan="5" class="r">부가세 (VAT)</td><td class="r">{{ number_format($tax['vat']) }}원</td></tr>
            @if ($tax['exempt'] > 0)
                <tr class="sub"><td colspan="5" class="r">면세 공급가액</td><td class="r">{{ number_format($tax['exempt']) }}원</td></tr>
            @endif
            <tr class="total"><td colspan="5" class="r">합계 (부가세 포함)</td><td class="r totalv">{{ number_format($tax['total']) }}원</td></tr>
        </tbody>
    </table>

    <div class="note">망고정 발주포털</div>
</div>
</body>
</html>
