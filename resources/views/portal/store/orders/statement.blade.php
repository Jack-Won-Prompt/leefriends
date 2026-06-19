<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>거래명세서 {{ $order->order_no }}</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            fontFamily: { sans: ['Pretendard Variable','Pretendard','sans-serif'] },
            colors: { mango: { 50:'#FFF9ED',400:'#FFB23D',500:'#FF9F1C',600:'#F2784B',700:'#D45A1F' } },
        }}}
    </script>
    <style>@media print { .no-print { display:none } body { -webkit-print-color-adjust:exact; print-color-adjust:exact } }</style>
</head>
<body class="font-sans bg-neutral-100 p-5">
@php
    $store = $order->store;
    $totalQty = $order->items->sum('qty');
    $totalAmount = $order->items->sum('store_line_amount');
@endphp
<div class="max-w-3xl mx-auto">
    <div class="flex justify-between items-center mb-4 no-print">
        <a href="{{ route('portal.store.orders.show', $order) }}" class="text-sm font-bold text-neutral-500 hover:text-mango-600">← 발주 상세</a>
        <button onclick="window.print()" class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-5 py-2.5">🖨️ 인쇄</button>
    </div>

    <div class="bg-white rounded-2xl shadow border border-neutral-200 overflow-hidden">
        <div class="bg-mango-500 text-white px-7 py-5 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-black tracking-wide">거 래 명 세 서</h1>
                <p class="text-white/80 text-sm mt-0.5">발주번호 {{ $order->order_no }}</p>
            </div>
            <div class="text-right text-sm">
                <p>{{ $order->created_at->format('Y년 m월 d일') }}</p>
            </div>
        </div>

        <div class="p-7">
            <div class="grid grid-cols-2 gap-4 mb-6">
                {{-- 공급자 = 본사 --}}
                <div class="rounded-xl border border-neutral-200 overflow-hidden">
                    <div class="bg-neutral-100 px-4 py-2 text-xs font-bold text-neutral-500">공급자 (본사)</div>
                    <table class="w-full text-sm">
                        <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400 w-20">상호</td><td class="px-4 py-2 font-bold">주식회사 오다네트웍스</td></tr>
                        <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400">대표자</td><td class="px-4 py-2">이윤석</td></tr>
                        <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400">등록번호</td><td class="px-4 py-2">827-81-03115</td></tr>
                        <tr><td class="px-4 py-2 text-neutral-400">주소</td><td class="px-4 py-2 text-neutral-600">경기도 의정부시 천보로 14, 1113호(민락동)</td></tr>
                    </table>
                </div>
                {{-- 받는 곳 = 매장 --}}
                <div class="rounded-xl border border-neutral-200 overflow-hidden">
                    <div class="bg-neutral-100 px-4 py-2 text-xs font-bold text-neutral-500">받는 곳 (매장)</div>
                    <table class="w-full text-sm">
                        <tr class="border-b border-neutral-100"><td class="px-4 py-2 text-neutral-400 w-20">매장</td><td class="px-4 py-2 font-bold">{{ $store->name ?? '-' }}</td></tr>
                        <tr><td class="px-4 py-2 text-neutral-400">주소</td><td class="px-4 py-2 text-neutral-600">{{ $store ? ($store->postcode ? '('.$store->postcode.') ' : '').$store->full_delivery_address : '-' }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="rounded-xl border border-neutral-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-neutral-100 text-neutral-500">
                        <tr>
                            <th class="text-left font-semibold px-4 py-2.5">품목</th>
                            <th class="text-left font-semibold px-4 py-2.5">공급</th>
                            <th class="text-left font-semibold px-4 py-2.5">단위</th>
                            <th class="text-right font-semibold px-4 py-2.5">수량</th>
                            <th class="text-right font-semibold px-4 py-2.5">단가</th>
                            <th class="text-right font-semibold px-4 py-2.5">금액</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($order->items as $it)
                            <tr>
                                <td class="px-4 py-2.5 font-semibold text-neutral-800">{{ $it->product_name }}</td>
                                <td class="px-4 py-2.5 text-neutral-500">{{ $it->supply_type === 'supplier' ? ($it->supplier_name ?? '공급처') : '본사' }}</td>
                                <td class="px-4 py-2.5 text-neutral-500">{{ $it->unit }}</td>
                                <td class="px-4 py-2.5 text-right">{{ number_format($it->qty) }}</td>
                                <td class="px-4 py-2.5 text-right">{{ number_format($it->store_unit_price) }}원</td>
                                <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($it->store_line_amount) }}원</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-neutral-50 font-black">
                            <td class="px-4 py-3" colspan="5">합계 (총 {{ number_format($totalQty) }}개)</td>
                            <td class="px-4 py-3 text-right text-mango-700">{{ number_format($totalAmount) }}원</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <p class="text-xs text-neutral-400 mt-5 text-center">본 명세서는 매장 구매가(부가세 포함) 기준으로 발행되었습니다. 공급 주체와 무관하게 본사가 공급자입니다. · LEEFRIENDS 발주포털</p>
        </div>
    </div>
</div>
</body>
</html>
