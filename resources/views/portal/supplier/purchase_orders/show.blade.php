@extends('portal.layout')
@section('title', '구매발주 ' . $po->po_no)

@section('content')
@php $chip = ['ordered'=>'bg-sky-100 text-sky-700','confirmed'=>'bg-amber-100 text-amber-700','received'=>'bg-emerald-100 text-emerald-700','canceled'=>'bg-neutral-100 text-neutral-400']; @endphp
<x-wms.page-head :title="'구매발주 ' . $po->po_no" :subtitle="'본사 → ' . $po->supplier_name . ' · ' . $po->created_at->format('Y-m-d H:i')" icon="🧾">
    <x-slot:actions>
        <span class="text-xs font-bold px-3 py-1.5 rounded-full {{ $chip[$po->status] ?? '' }}">{{ $po->status_label }}</span>
        @if ($po->status === 'ordered')
            <form method="POST" action="{{ route('portal.supplier.purchase_orders.confirm', $po) }}" onsubmit="return confirm('이 구매발주를 확인할까요?')">
                @csrf
                <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm">✅ 발주 확인</button>
            </form>
        @endif
    </x-slot:actions>
</x-wms.page-head>

@if ($errors->any())
    <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-5 py-3 text-sm">{{ $errors->first() }}</div>
@endif

<x-wms.panel>
    @if ($po->note)
        <div class="px-6 py-3 border-b border-neutral-100 text-sm text-neutral-500">메모: <b class="text-neutral-800">{{ $po->note }}</b></div>
    @endif
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-6 py-3">품목</th>
                <th class="text-left font-semibold px-6 py-3">단위</th>
                <th class="text-right font-semibold px-6 py-3">단가</th>
                <th class="text-right font-semibold px-6 py-3">수량</th>
                <th class="text-right font-semibold px-6 py-3">금액</th>
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
                </tr>
            @endforeach
            <tr class="bg-neutral-50 font-black">
                <td class="px-6 py-3.5" colspan="4">합계</td>
                <td class="px-6 py-3.5 text-right text-mango-700">{{ number_format($po->total_amount) }}원</td>
            </tr>
        </tbody>
    </table>
</x-wms.panel>

<div class="mt-5"><a href="{{ route('portal.supplier.purchase_orders.index') }}" class="text-sm font-bold text-neutral-500 hover:text-mango-600">← 목록으로</a></div>
@endsection
