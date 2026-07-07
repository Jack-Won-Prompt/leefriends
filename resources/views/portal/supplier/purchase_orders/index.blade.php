@extends('portal.layout')
@section('title', '본사 구매발주')

@section('content')
@php $chip = ['ordered'=>'bg-sky-100 text-sky-700','confirmed'=>'bg-amber-100 text-amber-700','received'=>'bg-emerald-100 text-emerald-700','canceled'=>'bg-neutral-100 text-neutral-400']; @endphp
<x-wms.page-head title="본사 구매발주" subtitle="본사가 우리 공급처에 등록한 매입 발주입니다." icon="🧾">
    <x-slot:actions>
        <form method="GET">
            <select name="status" onchange="this.form.submit()" class="rounded-xl border-neutral-200 text-sm py-2">
                <option value="all">전체 상태</option>
                @foreach (\App\Models\PurchaseOrder::STATUSES as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach
            </select>
        </form>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.panel>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-5 py-3">발주번호</th>
                    <th class="text-right font-semibold px-5 py-3">품목</th>
                    <th class="text-right font-semibold px-5 py-3">합계</th>
                    <th class="text-left font-semibold px-5 py-3">상태</th>
                    <th class="text-left font-semibold px-5 py-3 hidden md:table-cell">등록일</th>
                    <th class="text-right font-semibold px-5 py-3">관리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($orders as $o)
                    <tr class="hover:bg-mango-50/40">
                        <td class="px-5 py-3.5 font-bold text-mango-700 whitespace-nowrap">{{ $o->po_no }}</td>
                        <td class="px-5 py-3.5 text-right text-neutral-500">{{ $o->items()->count() }}건</td>
                        <td class="px-5 py-3.5 text-right font-black text-neutral-800 whitespace-nowrap">{{ number_format($o->total_amount) }}원</td>
                        <td class="px-5 py-3.5 whitespace-nowrap"><span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $chip[$o->status] ?? '' }}">{{ $o->status_label }}</span></td>
                        <td class="px-5 py-3.5 hidden md:table-cell text-neutral-500 whitespace-nowrap">{{ $o->created_at->format('Y-m-d') }}</td>
                        <td class="px-5 py-3.5 text-right"><a href="{{ route('portal.supplier.purchase_orders.show', $o) }}" class="text-xs font-bold text-neutral-600 hover:text-mango-600">상세</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-neutral-400">수신한 구매발주가 없습니다.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-wms.panel>

<div class="mt-5">{{ $orders->links() }}</div>
@endsection
