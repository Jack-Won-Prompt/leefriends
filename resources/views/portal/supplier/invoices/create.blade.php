@extends('portal.layout')
@section('title', '세금계산서 발행')

@section('content')
<a href="{{ route('portal.supplier.invoices.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600 mb-5">← 세금계산서 목록</a>

@if ($items->isEmpty())
    <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-12 text-center text-neutral-400">
        청구 가능한 <b>배송완료·미청구</b> 품목이 없습니다.
    </div>
@else
<form method="POST" action="{{ route('portal.supplier.invoices.store') }}"
      x-data="invoiceForm()" x-init="init()">
    @csrf
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-neutral-100 flex items-center justify-between">
                <span class="font-extrabold text-neutral-900">청구 대상 (배송완료 · 미청구)</span>
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
                <h3 class="font-extrabold text-neutral-900 mb-4">발행 정보</h3>
                <div class="flex justify-between text-sm py-1.5"><span class="text-neutral-500">공급가액</span><span class="font-bold"><span x-text="supply.toLocaleString()"></span>원</span></div>
                <div class="flex justify-between text-sm py-1.5"><span class="text-neutral-500">부가세 (10%)</span><span class="font-bold"><span x-text="vat.toLocaleString()"></span>원</span></div>
                <div class="border-t border-neutral-100 my-3"></div>
                <div class="flex justify-between items-end"><span class="text-neutral-600 font-semibold">합계금액</span><span class="text-2xl font-black text-mango-700"><span x-text="total.toLocaleString()"></span>원</span></div>

                <label class="block text-sm font-bold text-neutral-700 mt-5 mb-1.5">작성일자</label>
                <input type="date" name="issue_date" value="{{ now()->format('Y-m-d') }}" required
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">

                <textarea name="note" rows="2" placeholder="비고 (선택)"
                          class="w-full mt-3 rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm"></textarea>

                <button type="submit"
                        class="w-full mt-4 rounded-xl bg-gradient-to-r from-mango-500 to-mango-600 text-white font-black py-3.5 shadow hover:brightness-105 active:scale-[0.99] transition disabled:opacity-50"
                        x-bind:disabled="count === 0">세금계산서 발행</button>
                <p class="text-[11px] text-neutral-400 mt-3 leading-relaxed">* 본사가 정한 공급가 기준으로 본사에 청구됩니다. 추후 팝빌 전자세금계산서 연동 예정.</p>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    function invoiceForm() {
        return {
            supply: 0, vat: 0, total: 0, count: 0,
            init() { this.recalc(); },
            toggleAll(e) {
                document.querySelectorAll('.item-check').forEach(c => c.checked = e.target.checked);
                this.recalc();
            },
            recalc() {
                let supply = 0, count = 0;
                document.querySelectorAll('.item-check:checked').forEach(c => { supply += parseInt(c.dataset.amount || 0); count++; });
                this.supply = supply;
                this.vat = Math.round(supply * 0.1);
                this.total = this.supply + this.vat;
                this.count = count;
            },
        }
    }
</script>
@endpush
@endif
@endsection
