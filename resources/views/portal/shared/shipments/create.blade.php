@extends('portal.layout')
@section('title', '출고 생성')

@section('content')
<a href="{{ route($routePrefix . '.shipments.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600 mb-5">← 출고 목록</a>

<p class="text-sm text-neutral-500 mb-5">확인된 판매주문의 미출고 품목을 <b>매장별로 묶어</b> 출고를 생성합니다.</p>

@if ($grouped->isEmpty())
    <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-12 text-center text-neutral-400">
        출고 가능한(판매주문 확인 완료) 품목이 없습니다.
    </div>
@else
    <div class="space-y-6">
        @foreach ($grouped as $storeId => $items)
            @php $store = $stores[$storeId] ?? null; @endphp
            <form method="POST" action="{{ route($routePrefix . '.shipments.store') }}"
                  class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden"
                  x-data="{ checked: {{ $items->count() }} }">
                @csrf
                <input type="hidden" name="store_id" value="{{ $storeId }}">
                <div class="px-6 py-4 border-b border-neutral-100 flex items-center justify-between bg-neutral-50">
                    <div>
                        <span class="font-extrabold text-neutral-900">🏬 {{ $store->name ?? '매장' }}</span>
                        <span class="text-sm text-neutral-400 ml-2">{{ $store->address ?? '' }}</span>
                    </div>
                    <label class="text-sm font-semibold text-neutral-500 flex items-center gap-2">
                        <input type="checkbox" checked
                               @change="$root.querySelectorAll('.it-chk').forEach(c=>c.checked=$event.target.checked); checked = $event.target.checked ? {{ $items->count() }} : 0"
                               class="rounded text-mango-500 focus:ring-mango-400"> 전체선택
                    </label>
                </div>
                <table class="w-full text-sm">
                    <thead class="text-neutral-500">
                        <tr>
                            <th class="px-4 py-2 w-10"></th>
                            <th class="text-left font-semibold px-4 py-2">품목</th>
                            <th class="text-left font-semibold px-4 py-2">판매주문</th>
                            <th class="text-left font-semibold px-4 py-2">단위</th>
                            <th class="text-right font-semibold px-4 py-2">수량</th>
                            <th class="text-right font-semibold px-4 py-2">공급액</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($items as $it)
                            <tr>
                                <td class="px-4 py-2.5 text-center">
                                    <input type="checkbox" name="items[]" value="{{ $it->id }}" checked
                                           class="it-chk rounded text-mango-500 focus:ring-mango-400"
                                           @change="checked = $root.querySelectorAll('.it-chk:checked').length">
                                </td>
                                <td class="px-4 py-2.5 font-semibold text-neutral-800">{{ $it->product_name }}</td>
                                <td class="px-4 py-2.5 text-neutral-400 text-xs">{{ $it->salesOrder->sales_order_no ?? '' }}</td>
                                <td class="px-4 py-2.5 text-neutral-500">{{ $it->unit }}</td>
                                <td class="px-4 py-2.5 text-right">{{ number_format($it->qty) }}</td>
                                <td class="px-4 py-2.5 text-right">{{ number_format($it->supply_line_amount) }}원</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-6 py-4 border-t border-neutral-100 flex items-center gap-3">
                    <input type="text" name="note" placeholder="메모(선택)" class="flex-1 rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-6 py-2.5 transition disabled:opacity-50" x-bind:disabled="checked === 0">
                        이 매장 출고 생성 (<span x-text="checked"></span>건)
                    </button>
                </div>
            </form>
        @endforeach
    </div>
@endif
@endsection
