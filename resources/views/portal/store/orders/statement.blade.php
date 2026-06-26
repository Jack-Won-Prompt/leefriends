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

    @include('portal.partials.store-order-statement-document', ['order' => $order])
</div>
@if (request()->boolean('print'))
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
@endif
</body>
</html>
