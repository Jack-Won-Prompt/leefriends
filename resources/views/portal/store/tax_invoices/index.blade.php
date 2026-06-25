@extends('portal.layout')
@section('title', '세금계산서')

@section('content')
<x-wms.page-head title="세금계산서" subtitle="본사가 우리 매장 앞으로 발행한 세금계산서" icon="🧾" />

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    @if ($invoices->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">발행된 세금계산서가 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">계산서번호</th>
                        <th class="text-left font-semibold px-6 py-3">구분</th>
                        <th class="text-left font-semibold px-6 py-3">공급자</th>
                        <th class="text-right font-semibold px-6 py-3">공급가액</th>
                        <th class="text-right font-semibold px-6 py-3">부가세</th>
                        <th class="text-right font-semibold px-6 py-3">합계</th>
                        <th class="text-left font-semibold px-6 py-3">발행일</th>
                        <th class="text-left font-semibold px-6 py-3">상태</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($invoices as $inv)
                        @php($isExempt = str_contains($inv->note ?? '', '면세'))
                        <tr class="hover:bg-mango-50/40 transition cursor-pointer" onclick="location.href='{{ route('portal.store.tax_invoices.show', $inv) }}'">
                            <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $inv->invoice_no }}</td>
                            <td class="px-6 py-3.5">
                                <span class="text-xs font-bold px-2 py-1 rounded-full {{ $isExempt ? 'bg-sky-100 text-sky-700' : 'bg-mango-100 text-mango-700' }}">{{ $isExempt ? '계산서(면세)' : '세금계산서' }}</span>
                            </td>
                            <td class="px-6 py-3.5">{{ $inv->invoicer_corp_name }}</td>
                            <td class="px-6 py-3.5 text-right">{{ number_format($inv->supply_amount) }}원</td>
                            <td class="px-6 py-3.5 text-right text-neutral-500">{{ number_format($inv->vat) }}원</td>
                            <td class="px-6 py-3.5 text-right font-black text-mango-700">{{ number_format($inv->total_amount) }}원</td>
                            <td class="px-6 py-3.5 text-neutral-400">{{ $inv->issue_date?->format('Y.m.d') }}</td>
                            <td class="px-6 py-3.5">
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $inv->status === 'canceled' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $inv->status_label }}</span>
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
