@extends('portal.layout')
@section('title', '배송 관리')

@section('content')
<div class="flex flex-wrap gap-2 mb-6">
    <a href="{{ route('portal.supplier.fulfillment.index') }}"
       class="px-4 py-2 rounded-full text-sm font-bold transition {{ $status === 'all' ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 hover:bg-mango-50' }}">전체</a>
    @foreach ($statuses as $key => $label)
        <a href="{{ route('portal.supplier.fulfillment.index', ['status' => $key]) }}"
           class="px-4 py-2 rounded-full text-sm font-bold transition {{ $status === $key ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 hover:bg-mango-50' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">매장 직배송 품목</div>
    @if ($items->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">해당 상태의 배송 품목이 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">주문번호</th>
                        <th class="text-left font-semibold px-6 py-3">배송지 (매장)</th>
                        <th class="text-left font-semibold px-6 py-3">품목</th>
                        <th class="text-right font-semibold px-6 py-3">수량</th>
                        <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">공급액</th>
                        <th class="text-left font-semibold px-6 py-3">상태</th>
                        <th class="text-right font-semibold px-6 py-3 w-56">배송 처리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($items as $it)
                        <tr class="hover:bg-mango-50/40 transition">
                            <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $it->order->order_no ?? '-' }}</td>
                            <td class="px-6 py-3.5">
                                <p class="font-semibold">{{ $it->order->store->name ?? '-' }}</p>
                                <p class="text-xs text-neutral-400">{{ $it->order->store->address ?? '' }}</p>
                            </td>
                            <td class="px-6 py-3.5">{{ $it->product_name }}</td>
                            <td class="px-6 py-3.5 text-right">{{ number_format($it->qty) }}{{ $it->unit }}</td>
                            <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ number_format($it->supply_line_amount) }}원</td>
                            <td class="px-6 py-3.5">@include('portal.partials.fulfillment-status', ['status' => $it->fulfillment_status, 'label' => $it->fulfillment_label])</td>
                            <td class="px-6 py-3.5">
                                <div class="flex justify-end gap-1.5">
                                    @foreach (['shipping' => '배송중', 'delivered' => '배송완료'] as $st => $lbl)
                                        @if ($it->fulfillment_status !== $st)
                                            <form method="POST" action="{{ route('portal.supplier.fulfillment.update', $it) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="fulfillment_status" value="{{ $st }}">
                                                <button class="rounded-lg px-3 py-1.5 font-semibold text-xs {{ $st === 'delivered' ? 'bg-emerald-500 text-white hover:bg-emerald-600' : 'bg-sky-100 text-sky-700 hover:bg-sky-200' }}">{{ $lbl }}</button>
                                            </form>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="mt-6">{{ $items->links() }}</div>
@endsection
