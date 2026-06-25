@extends('portal.layout')
@section('title', '거래명세서 작성')

@section('content')
<a href="{{ route('portal.supplier.statements.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600 mb-5">← 거래명세서 이력</a>

@if ($items->isEmpty())
    <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-12 text-center text-neutral-400">
        거래명세서로 담을 수 있는 <b>배송완료 · 미청구</b> 품목이 없습니다.
    </div>
@else
<form method="POST" action="{{ route('portal.supplier.statements.store') }}" x-data="stmtForm()" x-init="init()">
    @csrf
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-100 flex items-center justify-between">
                <span class="font-extrabold text-neutral-900">담을 품목 (배송완료 · 미청구)</span>
                <label class="text-sm font-semibold text-neutral-500 flex items-center gap-2">
                    <input type="checkbox" @change="toggleAll($event)" checked class="rounded text-mango-500 focus:ring-mango-400"> 전체선택
                </label>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-neutral-50 text-neutral-500">
                        <tr>
                            <th class="px-4 py-3 w-10"></th>
                            <th class="text-left font-semibold px-4 py-3">주문 / 매장</th>
                            <th class="text-left font-semibold px-4 py-3">품목</th>
                            <th class="text-right font-semibold px-4 py-3">공급단가</th>
                            <th class="text-right font-semibold px-4 py-3">수량</th>
                            <th class="text-right font-semibold px-4 py-3">공급가액</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($items as $it)
                            <tr>
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox" name="items[]" value="{{ $it->id }}" checked
                                           data-amount="{{ $it->supply_line_amount }}" @change="recalc()"
                                           class="item-check rounded text-mango-500 focus:ring-mango-400">
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-neutral-800">{{ $it->order->store->name ?? '-' }}</p>
                                    <p class="text-xs text-neutral-400">{{ $it->order->order_no ?? '' }}</p>
                                </td>
                                <td class="px-4 py-3">{{ $it->product_name }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($it->supply_unit_price) }}원</td>
                                <td class="px-4 py-3 text-right">{{ number_format($it->qty) }}{{ $it->unit }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ number_format($it->supply_line_amount) }}원</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-6 sticky top-20">
                <h3 class="font-extrabold text-neutral-900 mb-4">거래명세서 요약</h3>
                <div class="flex justify-between text-sm py-1.5"><span class="text-neutral-500">공급가액(합계)</span><span class="font-bold"><span x-text="supply.toLocaleString()"></span>원</span></div>
                <p class="text-[11px] text-neutral-400 mb-3">* 부가세는 제품별 부가세구분(포함/별도/면세)에 따라 발행 시 계산됩니다.</p>
                <div class="border-t border-neutral-100 my-3"></div>
                <div class="flex justify-between items-end"><span class="text-neutral-600 font-semibold">선택 품목</span><span class="text-2xl font-black text-mango-700"><span x-text="count"></span>건</span></div>

                <button type="submit"
                        class="w-full mt-5 rounded-xl bg-gradient-to-r from-mango-500 to-mango-600 text-white font-black py-3.5 shadow hover:brightness-105 active:scale-[0.99] transition disabled:opacity-50"
                        x-bind:disabled="count === 0">거래명세서 작성·저장</button>
                <p class="text-[11px] text-neutral-400 mt-3 leading-relaxed">* 저장 후 거래명세서 이력에서 <b>세금계산서 발행</b>을 진행합니다.</p>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    function stmtForm() {
        return {
            supply: 0, count: 0,
            init() { this.recalc(); },
            toggleAll(e) {
                document.querySelectorAll('.item-check').forEach(c => c.checked = e.target.checked);
                this.recalc();
            },
            recalc() {
                let supply = 0, count = 0;
                document.querySelectorAll('.item-check:checked').forEach(c => { supply += parseInt(c.dataset.amount || 0); count++; });
                this.supply = supply; this.count = count;
            },
        }
    }
</script>
@endpush
@endif
@endsection
