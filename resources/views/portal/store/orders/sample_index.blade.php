@extends('portal.layout')
@section('title', '샘플 주문 내역')

@section('content')
<x-wms.page-head title="샘플 주문 내역" subtitle="신상품·시식용 샘플 주문 내역을 조회합니다 (무상)" icon="🧪">
    <x-slot:actions>
        <a href="{{ route('portal.store.sample_orders.create') }}" class="inline-flex items-center gap-1 rounded-xl bg-violet-500 hover:bg-violet-600 text-white font-bold px-4 py-2 text-sm transition">🧪 샘플 주문하기</a>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.toolbar :count="$orders->total()" />

<x-wms.panel>
    @if ($orders->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">샘플 주문 내역이 없습니다.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">주문번호</th>
                    <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">품목수</th>
                    <th class="text-left font-semibold px-6 py-3">구분</th>
                    <th class="text-left font-semibold px-6 py-3">상태</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">주문일</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($orders as $o)
                    <tr class="hover:bg-violet-50/40 transition cursor-pointer" onclick="location.href='{{ route('portal.store.orders.show', $o) }}'">
                        <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $o->order_no }}</td>
                        <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ $o->items_count }}</td>
                        <td class="px-6 py-3.5"><span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-violet-100 text-violet-700">샘플 · 무상</span></td>
                        <td class="px-6 py-3.5">@include('portal.partials.order-status', ['status' => $o->status, 'label' => $o->status_label])</td>
                        <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $o->created_at->format('Y.m.d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-wms.panel>

<div class="mt-5">{{ $orders->links() }}</div>
@endsection
