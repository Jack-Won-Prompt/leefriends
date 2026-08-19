<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>출고지시서</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    body { font-family: 'Pretendard Variable', Pretendard, -apple-system, 'Malgun Gothic', sans-serif; color: #1f2937; font-size: 15px; background: #f3f4f6; }
    .slip { background: #fff; width: 210mm; min-height: 297mm; margin: 12px auto; padding: 15mm 13mm; page-break-after: always; }
    .slip:last-child { page-break-after: auto; }

    .head { display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 3px solid #28798B; padding-bottom: 12px; }
    .head h1 { font-size: 34px; letter-spacing: 12px; color: #111827; }
    .head .meta { text-align: right; font-size: 15px; color: #4b5563; line-height: 1.7; }

    .store { margin-top: 16px; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
    .store table { width: 100%; border-collapse: collapse; }
    .store td { padding: 11px 14px; font-size: 16px; border-bottom: 1px solid #f1f1f1; }
    .store tr:last-child td { border-bottom: 0; }
    .store .lbl { background: #f9fafb; color: #6b7280; font-weight: 700; width: 92px; font-size: 15px; }

    .order { margin-top: 18px; border: 1px solid #e5e7eb; border-radius: 8px; page-break-inside: avoid; }
    .order .bar { display: flex; justify-content: space-between; align-items: center; gap: 12px; background: #fff7ed; padding: 14px 16px; border-bottom: 1px solid #fed7aa; }
    .order .bar .no { font-size: 24px; font-weight: 800; color: #9a3412; letter-spacing: 1px; }
    .order .bar .sub { font-size: 14px; color: #6b7280; margin-top: 4px; }
    .order .bar .qr { width: 108px; height: 108px; flex: 0 0 108px; }
    .order .bar .qr svg { width: 100%; height: 100%; display: block; }

    table.items { width: 100%; border-collapse: collapse; }
    table.items th { background: #f3f4f6; color: #4b5563; font-weight: 700; font-size: 14px; padding: 11px 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
    table.items td { padding: 12px; border-bottom: 1px solid #f4f4f5; font-size: 16px; }
    table.items td b { font-size: 17px; }
    table.items tr { page-break-inside: avoid; }
    .r { text-align: right; } .c { text-align: center; }
    .sup-hq { color: #b45309; font-weight: 700; }
    .sup-dir { color: #0369a1; font-weight: 700; }
    .tfoot td { background: #fafafa; font-weight: 800; font-size: 18px; }
    .note { margin-top: 8px; padding: 12px 14px; font-size: 15px; color: #4b5563; background: #fffbeb; border: 1px dashed #fcd34d; border-radius: 6px; }

    .sign { display: flex; gap: 12px; margin-top: 22px; }
    .sign .b { flex: 1; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px 14px; height: 72px; font-size: 14px; color: #9ca3af; }

    @media print {
        body { background: #fff; }
        .slip { width: auto; min-height: auto; margin: 0; padding: 12mm; box-shadow: none; }
        .toolbar { display: none !important; }
    }
    .toolbar { position: fixed; top: 12px; right: 16px; z-index: 10; }
    .toolbar button { font-family: inherit; background: #111827; color: #fff; border: 0; border-radius: 8px; padding: 10px 18px; font-size: 13px; font-weight: 700; cursor: pointer; }
</style>
</head>
<body>
<div class="toolbar"><button type="button" onclick="window.print()">🖨️ 인쇄</button></div>

@foreach ($grouped as $storeId => $orders)
    @php $store = $orders->first()->store; $storeQty = $orders->sum(fn ($o) => $o->items->sum('qty')); @endphp
    <div class="slip">
        <div class="head">
            <h1>출고지시서</h1>
            <div class="meta">
                출력일 {{ $printedAt->format('Y년 m월 d일 H:i') }}<br>
                발주 {{ $orders->count() }}건 · 총 {{ number_format($storeQty) }}개
            </div>
        </div>

        <div class="store">
            <table>
                <tr>
                    <td class="lbl">매장</td><td><b>{{ $store->name ?? '-' }}</b></td>
                    <td class="lbl">연락처</td><td>{{ $store->phone ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="lbl">배송지</td>
                    <td colspan="3">{{ $store ? (($store->postcode ? '('.$store->postcode.') ' : '').$store->full_delivery_address) : '-' }}</td>
                </tr>
            </table>
        </div>

        @foreach ($orders as $o)
            <div class="order">
                <div class="bar">
                    <div>
                        <div class="no">{{ $o->order_no }}</div>
                        <div class="sub">발주일 {{ $o->created_at->format('Y-m-d H:i') }} · 발주자 {{ $o->user->name ?? '-' }} · 상태 {{ $o->status_label }}</div>
                    </div>
                    <div class="qr" title="발주번호 {{ $o->order_no }}">{!! $qr[$o->id] !!}</div>
                </div>
                <table class="items">
                    <thead>
                        <tr>
                            <th style="width:8%;" class="c">No</th>
                            <th style="width:56%;">품목</th>
                            <th style="width:18%;">규격</th>
                            <th style="width:18%;" class="r">수량</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($o->items as $i => $it)
                            <tr>
                                <td class="c">{{ $i + 1 }}</td>
                                <td><b>{{ $it->product_name }}</b></td>
                                <td>{{ $it->unit }}</td>
                                <td class="r">{{ number_format($it->qty) }}{{ $it->unit }}</td>
                            </tr>
                        @endforeach
                        <tr class="tfoot">
                            <td colspan="3" class="r">합계 수량</td>
                            <td class="r">{{ number_format($o->items->sum('qty')) }}</td>
                        </tr>
                    </tbody>
                </table>
                @if ($o->note)
                    <div class="note">📝 요청사항: {{ $o->note }}</div>
                @endif
            </div>
        @endforeach

        <div class="sign">
            <div class="b">피킹 담당</div>
            <div class="b">검수 확인</div>
            <div class="b">출고 확인</div>
        </div>
    </div>
@endforeach

<script>
    window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 350); });
</script>
</body>
</html>
