@extends('portal.layout')
@section('title', '거래명세서 상세')

@section('content')
<div class="flex items-center justify-between mb-5 print:hidden">
    <a href="{{ route('portal.supplier.statements.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600">← 거래명세서 이력</a>
    <div class="flex items-center gap-2">
        @if ($statement->tax_invoice_id)
            <span class="rounded-xl bg-emerald-100 text-emerald-700 font-bold px-4 py-2.5 text-sm">세금계산서 발행완료
                @if (optional($statement->taxInvoice)->invoice_no) · {{ $statement->taxInvoice->invoice_no }} @endif
            </span>
            <a href="{{ route('portal.supplier.invoices.show', $statement->tax_invoice_id) }}" class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-5 py-2.5 text-sm transition">세금계산서 보기</a>
        @else
            <form method="POST" action="{{ route('portal.supplier.statements.issue', $statement) }}"
                  onsubmit="return confirm('이 거래명세서로 세금계산서를 발행합니다. (본사 청구)\n진행하시겠습니까?')">
                @csrf
                <button type="submit" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2.5 text-sm transition">🧾 세금계산서 발행</button>
            </form>
        @endif
    </div>
</div>

<div class="max-w-4xl bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden">
    <div class="bg-mango-500 text-white px-7 py-5 flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black tracking-wide">거 래 명 세 서</h2>
            <p class="text-white/80 text-sm mt-0.5">공급자: {{ $statement->supplier_name }} · 공급받는자: 주식회사 오다네트웍스(본사)</p>
        </div>
        <div class="text-right text-sm">
            <p class="font-bold">{{ $statement->statement_no }}</p>
            <p class="text-white/80">작성일 {{ $statement->created_at->format('Y년 m월 d일') }}</p>
        </div>
    </div>

    <div class="p-7">
        <div class="grid grid-cols-3 gap-3 mb-6">
            <div class="rounded-xl bg-neutral-50 p-4 text-center">
                <p class="text-xs text-neutral-500 font-semibold">공급가액</p>
                <p class="text-lg font-black text-neutral-900 mt-1">{{ number_format($statement->supply_total) }}원</p>
            </div>
            <div class="rounded-xl bg-neutral-50 p-4 text-center">
                <p class="text-xs text-neutral-500 font-semibold">부가세</p>
                <p class="text-lg font-black text-neutral-900 mt-1">{{ number_format($statement->vat) }}원</p>
            </div>
            <div class="rounded-xl bg-mango-500 text-white p-4 text-center">
                <p class="text-xs text-white/80 font-semibold">합계금액</p>
                <p class="text-lg font-black mt-1">{{ number_format($statement->total) }}원</p>
            </div>
        </div>

        <div class="rounded-xl border border-neutral-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-neutral-100 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-4 py-2.5">품목 (주문)</th>
                        <th class="text-right font-semibold px-4 py-2.5">공급단가</th>
                        <th class="text-right font-semibold px-4 py-2.5">수량</th>
                        <th class="text-right font-semibold px-4 py-2.5">공급가액</th>
                        <th class="text-right font-semibold px-4 py-2.5">세액</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($statement->items ?? [] as $l)
                        <tr>
                            <td class="px-4 py-2.5">
                                <span class="font-semibold text-neutral-800">{{ $l['name'] ?? '-' }}</span>
                                <span class="text-xs text-neutral-400 ml-1">· {{ $l['store_name'] ?? '' }} ({{ $l['order_no'] ?? '' }})</span>
                            </td>
                            <td class="px-4 py-2.5 text-right">{{ number_format($l['unit_price'] ?? 0) }}원</td>
                            <td class="px-4 py-2.5 text-right">{{ number_format($l['qty'] ?? 0) }}{{ $l['unit'] ?? '' }}</td>
                            <td class="px-4 py-2.5 text-right font-semibold">{{ number_format($l['supply'] ?? 0) }}원</td>
                            <td class="px-4 py-2.5 text-right text-neutral-500">{{ number_format($l['tax'] ?? 0) }}원</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-neutral-50 font-black">
                        <td class="px-4 py-3" colspan="3">합계</td>
                        <td class="px-4 py-3 text-right text-mango-700">{{ number_format($statement->supply_total) }}원</td>
                        <td class="px-4 py-3 text-right text-neutral-600">{{ number_format($statement->vat) }}원</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
