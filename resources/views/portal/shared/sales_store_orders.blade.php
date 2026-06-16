{{-- 매장별 발주 상세 팝업 내용 (HTML fragment). $store, $orders, $detailRoute, $showCost --}}
<div class="px-6 py-4 border-b border-neutral-100 sticky top-0 bg-white">
    <h3 class="text-lg font-extrabold text-neutral-900">{{ $store->name }} <span class="text-sm font-normal text-neutral-400">{{ $store->region }}</span></h3>
    <p class="text-xs text-neutral-400 mt-0.5">발주 상세 · {{ number_format($orders->count()) }}건</p>
</div>

@if ($orders->isEmpty())
    <p class="px-6 py-16 text-center text-neutral-400">해당 기간의 발주가 없습니다.</p>
@else
    <div class="overflow-x-auto max-h-[60vh]">
        <table class="w-full text-sm whitespace-nowrap">
            <thead class="bg-neutral-50 text-neutral-500 sticky top-0">
                <tr>
                    <th class="text-left font-semibold px-5 py-2.5">주문번호</th>
                    <th class="text-left font-semibold px-5 py-2.5">발주일</th>
                    <th class="text-right font-semibold px-5 py-2.5">품목</th>
                    @if ($showCost)
                        <th class="text-right font-semibold px-5 py-2.5">출고가</th>
                        <th class="text-right font-semibold px-5 py-2.5">원가</th>
                    @else
                        <th class="text-right font-semibold px-5 py-2.5">자사 공급액</th>
                    @endif
                    <th class="text-left font-semibold px-5 py-2.5">상태</th>
                    <th class="text-right font-semibold px-5 py-2.5">상세</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($orders as $o)
                    @php
                        $cnt = $o->items_count ?? ($o->relationLoaded('items') ? $o->items->count() : 0);
                        $supplyOwn = $o->relationLoaded('items') ? $o->items->sum('supply_line_amount') : $o->supply_amount;
                    @endphp
                    <tr class="hover:bg-mango-50/40">
                        <td class="px-5 py-2.5 font-bold text-neutral-900 font-mono">{{ $o->order_no }}</td>
                        <td class="px-5 py-2.5 text-neutral-500">{{ $o->created_at->format('Y.m.d H:i') }}</td>
                        <td class="px-5 py-2.5 text-right text-neutral-500">{{ number_format($cnt) }}</td>
                        @if ($showCost)
                            <td class="px-5 py-2.5 text-right font-semibold text-mango-700">{{ number_format($o->store_amount) }}원</td>
                            <td class="px-5 py-2.5 text-right text-neutral-500">{{ number_format($o->supply_amount) }}원</td>
                        @else
                            <td class="px-5 py-2.5 text-right font-semibold text-mango-700">{{ number_format($supplyOwn) }}원</td>
                        @endif
                        <td class="px-5 py-2.5">@include('portal.partials.order-status', ['status' => $o->status, 'label' => $o->status_label])</td>
                        <td class="px-5 py-2.5 text-right">
                            <a href="{{ route($detailRoute, $o) }}" target="_blank" class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1 font-semibold inline-block">열기 ↗</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
