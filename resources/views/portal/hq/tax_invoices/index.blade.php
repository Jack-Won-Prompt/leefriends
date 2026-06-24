@extends('portal.layout')
@section('title', '세금계산서 (발행)')

@section('content')
<x-wms.page-head title="세금계산서 (발행)" subtitle="본사 → 매장 발행 내역" icon="🧾" />

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900 flex items-center justify-between">
        <span>본사 발행 세금계산서</span>
        <span class="text-xs font-semibold text-neutral-400">발주 상세에서 “세금계산서 발행” 버튼으로 발행</span>
    </div>
    @if ($invoices->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">발행한 세금계산서가 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">계산서번호</th>
                        <th class="text-left font-semibold px-6 py-3">구분</th>
                        <th class="text-left font-semibold px-6 py-3">공급받는자(매장)</th>
                        <th class="text-right font-semibold px-6 py-3">공급가액</th>
                        <th class="text-right font-semibold px-6 py-3">부가세</th>
                        <th class="text-right font-semibold px-6 py-3">합계</th>
                        <th class="text-left font-semibold px-6 py-3">발행일</th>
                        <th class="text-left font-semibold px-6 py-3">상태</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($invoices as $inv)
                        <tr class="hover:bg-mango-50/40 transition">
                            <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $inv->invoice_no }}</td>
                            <td class="px-6 py-3.5">
                                @php($isExempt = str_contains($inv->note ?? '', '면세'))
                                <span class="text-xs font-bold px-2 py-1 rounded-full {{ $isExempt ? 'bg-sky-100 text-sky-700' : 'bg-mango-100 text-mango-700' }}">
                                    {{ $isExempt ? '계산서(면세)' : '세금계산서' }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5">
                                {{ $inv->invoicee_corp_name ?? optional($inv->store)->name ?? '-' }}
                                <span class="block text-xs text-neutral-400">{{ $inv->invoicee_email }}</span>
                            </td>
                            <td class="px-6 py-3.5 text-right">{{ number_format($inv->supply_amount) }}원</td>
                            <td class="px-6 py-3.5 text-right text-neutral-500">{{ number_format($inv->vat) }}원</td>
                            <td class="px-6 py-3.5 text-right font-black text-mango-700">{{ number_format($inv->total_amount) }}원</td>
                            <td class="px-6 py-3.5 text-neutral-400">{{ $inv->issue_date?->format('Y.m.d') }}</td>
                            <td class="px-6 py-3.5">
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $inv->status === 'canceled' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $inv->status_label }}</span>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($inv->order_id)
                                        <a href="{{ route('portal.hq.orders.show', $inv->order_id) }}" class="text-xs font-bold text-mango-600 hover:text-mango-700">발주보기 →</a>
                                    @endif
                                    @if ($inv->status === 'issued')
                                        <form method="POST" action="{{ route('portal.hq.tax_invoices.cancel', $inv) }}"
                                              onsubmit="return confirm('이 세금계산서를 발행취소합니다. 진행하시겠습니까?\n(국세청 전송 완료 후에는 취소되지 않을 수 있습니다.)')">
                                            @csrf
                                            <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-700">발행취소</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="mt-6">{{ $invoices->links() }}</div>
@endsection
