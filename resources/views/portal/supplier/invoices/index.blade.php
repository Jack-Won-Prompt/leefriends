@extends('portal.layout')
@section('title', '세금계산서 발행')

@section('content')
<x-wms.page-head title="세금계산서 발행" subtitle="배송완료 건을 본사에 청구·발행합니다" icon="🧾" />
<div class="grid md:grid-cols-3 gap-4 mb-6">
    <div class="md:col-span-2 rounded-2xl bg-gradient-to-br from-mango-500 to-mango-600 text-white p-6 flex items-center justify-between">
        <div>
            <p class="text-white/80 font-semibold text-sm">미청구 (배송완료) 금액</p>
            <p class="text-3xl font-black mt-1">{{ number_format($pending['amount']) }}원</p>
            <p class="text-white/80 text-sm mt-1">{{ number_format($pending['count']) }}개 품목 · 공급가 기준</p>
        </div>
        @if ($pending['count'] > 0)
            <a href="{{ route('portal.supplier.invoices.create') }}" class="rounded-xl bg-white text-mango-700 font-bold px-6 py-3.5 shadow hover:scale-105 transition shrink-0">세금계산서 발행 →</a>
        @endif
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-neutral-100">
        <p class="text-sm text-neutral-500 font-medium">발행 건수</p>
        <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($invoices->total()) }}건</p>
    </div>
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">발행 내역 (본사 청구)</div>
    @if ($invoices->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">발행한 세금계산서가 없습니다.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">계산서번호</th>
                    <th class="text-left font-semibold px-6 py-3">구분</th>
                    <th class="text-right font-semibold px-6 py-3">공급가액</th>
                    <th class="text-right font-semibold px-6 py-3">부가세</th>
                    <th class="text-right font-semibold px-6 py-3">합계</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">작성일</th>
                    <th class="text-left font-semibold px-6 py-3">발행</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($invoices as $inv)
                    @php($isExempt = str_contains($inv->note ?? '', '면세'))
                    <tr class="hover:bg-mango-50/40 transition cursor-pointer" onclick="location.href='{{ route('portal.supplier.invoices.show', $inv) }}'">
                        <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $inv->invoice_no }}</td>
                        <td class="px-6 py-3.5">
                            <span class="text-xs font-bold px-2 py-1 rounded-full {{ $isExempt ? 'bg-sky-100 text-sky-700' : 'bg-mango-100 text-mango-700' }}">{{ $isExempt ? '계산서(면세)' : '세금계산서' }}</span>
                        </td>
                        <td class="px-6 py-3.5 text-right">{{ number_format($inv->supply_amount) }}원</td>
                        <td class="px-6 py-3.5 text-right text-neutral-500">{{ number_format($inv->vat) }}원</td>
                        <td class="px-6 py-3.5 text-right font-black text-mango-700">{{ number_format($inv->total_amount) }}원</td>
                        <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $inv->issue_date?->format('Y.m.d') }}</td>
                        <td class="px-6 py-3.5">
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $inv->status === 'canceled' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $inv->status_label }}</span>
                            <span class="text-[10px] text-neutral-400 ml-1">{{ $inv->provider === 'popbill' ? '팝빌' : '내부' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="mt-6">{{ $invoices->links() }}</div>
@endsection
