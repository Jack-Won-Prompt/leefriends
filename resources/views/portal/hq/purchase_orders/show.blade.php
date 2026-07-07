@extends('portal.layout')
@section('title', '구매발주 ' . $po->po_no)

@section('content')
@php $chip = ['ordered'=>'bg-sky-100 text-sky-700','confirmed'=>'bg-amber-100 text-amber-700','received'=>'bg-emerald-100 text-emerald-700','canceled'=>'bg-neutral-100 text-neutral-400']; @endphp
<x-wms.page-head :title="'구매발주 ' . $po->po_no" :subtitle="$po->supplier_name . ' · ' . $po->created_at->format('Y-m-d H:i')" icon="🧾">
    <x-slot:actions>
        <span class="text-xs font-bold px-3 py-1.5 rounded-full {{ $chip[$po->status] ?? '' }}">{{ $po->status_label }}</span>
        @if (! in_array($po->status, ['received','canceled'], true))
            <form method="POST" action="{{ route('portal.hq.purchase_orders.receive', $po) }}" onsubmit="return confirm('입고 처리하면 본사 재고에 반영됩니다. 진행할까요?')">
                @csrf
                <button class="rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-4 py-2 text-sm">📥 입고 처리</button>
            </form>
            <form method="POST" action="{{ route('portal.hq.purchase_orders.cancel', $po) }}" onsubmit="return confirm('이 구매발주를 취소할까요?')">
                @csrf
                <button class="rounded-xl bg-neutral-100 hover:bg-rose-50 text-rose-600 font-bold px-4 py-2 text-sm">취소</button>
            </form>
        @endif
    </x-slot:actions>
</x-wms.page-head>

@if ($errors->any())
    <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-5 py-3 text-sm">{{ $errors->first() }}</div>
@endif

<x-wms.panel>
    <div class="px-6 py-4 border-b border-neutral-100 flex flex-wrap gap-x-8 gap-y-1 text-sm">
        <span class="text-neutral-400">공급처 <b class="text-neutral-800 ml-1">{{ $po->supplier_name }}</b></span>
        <span class="text-neutral-400">등록자 <b class="text-neutral-800 ml-1">{{ optional($po->creator)->name ?? '본사' }}</b></span>
        @if ($po->confirmed_at)<span class="text-neutral-400">확인 <b class="text-neutral-800 ml-1">{{ $po->confirmed_at->format('Y-m-d H:i') }}</b></span>@endif
        @if ($po->received_at)<span class="text-neutral-400">입고 <b class="text-emerald-700 ml-1">{{ $po->received_at->format('Y-m-d H:i') }}</b></span>@endif
        @if ($po->note)<span class="text-neutral-400">메모 <b class="text-neutral-800 ml-1">{{ $po->note }}</b></span>@endif
    </div>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-6 py-3">품목</th>
                <th class="text-left font-semibold px-6 py-3">단위</th>
                <th class="text-right font-semibold px-6 py-3">단가</th>
                <th class="text-right font-semibold px-6 py-3">수량</th>
                <th class="text-right font-semibold px-6 py-3">금액</th>
                <th class="text-right font-semibold px-6 py-3">입고</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @foreach ($po->items as $it)
                <tr>
                    <td class="px-6 py-3 font-semibold text-neutral-800">{{ $it->product_name }}</td>
                    <td class="px-6 py-3 text-neutral-500">{{ $it->unit }}</td>
                    <td class="px-6 py-3 text-right">{{ number_format($it->unit_price) }}원</td>
                    <td class="px-6 py-3 text-right">{{ number_format($it->qty) }}</td>
                    <td class="px-6 py-3 text-right font-bold">{{ number_format($it->line_amount) }}원</td>
                    <td class="px-6 py-3 text-right text-neutral-500">{{ number_format($it->received_qty) }}</td>
                </tr>
            @endforeach
            <tr class="bg-neutral-50 font-black">
                <td class="px-6 py-3.5" colspan="4">합계</td>
                <td class="px-6 py-3.5 text-right text-mango-700">{{ number_format($po->total_amount) }}원</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</x-wms.panel>

<div class="mt-5"><a href="{{ route('portal.hq.purchase_orders.index') }}" class="text-sm font-bold text-neutral-500 hover:text-mango-600">← 목록으로</a></div>
@endsection
