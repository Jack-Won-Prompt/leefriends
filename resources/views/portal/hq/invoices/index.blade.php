@extends('portal.layout')
@section('title', '세금계산서 (수취)')

@section('content')
<div x-data="{ open: null }">
<x-wms.page-head title="세금계산서 (수취)" subtitle="공급처가 발행한 세금계산서" icon="🧮" />
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="rounded-2xl bg-white p-5 shadow-sm border border-neutral-100">
        <p class="text-sm text-neutral-500 font-medium">발행 건수</p>
        <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($totals['count']) }}건</p>
    </div>
    <div class="rounded-2xl bg-neutral-900 text-white p-5">
        <p class="text-sm text-white/70 font-medium">수취 합계금액</p>
        <p class="text-3xl font-black text-mango-300 mt-1">{{ number_format($totals['amount']) }}원</p>
    </div>
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">공급처 발행 세금계산서</div>
    @if ($invoices->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">수취한 세금계산서가 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">계산서번호</th>
                        <th class="text-left font-semibold px-6 py-3">공급처</th>
                        <th class="text-right font-semibold px-6 py-3">공급가액</th>
                        <th class="text-right font-semibold px-6 py-3">부가세</th>
                        <th class="text-right font-semibold px-6 py-3">합계</th>
                        <th class="text-left font-semibold px-6 py-3">작성일</th>
                        <th class="text-left font-semibold px-6 py-3">상태</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($invoices as $inv)
                        <tr class="hover:bg-mango-50/40 transition cursor-pointer" @click="open = {{ $inv->id }}">
                            <td class="px-6 py-3.5 font-bold text-mango-700">{{ $inv->invoice_no }}</td>
                            <td class="px-6 py-3.5">{{ $inv->invoicer_corp_name ?? optional($inv->supplier)->name ?? '-' }}</td>
                            <td class="px-6 py-3.5 text-right">{{ number_format($inv->supply_amount) }}원</td>
                            <td class="px-6 py-3.5 text-right text-neutral-500">{{ number_format($inv->vat) }}원</td>
                            <td class="px-6 py-3.5 text-right font-black text-mango-700">{{ number_format($inv->total_amount) }}원</td>
                            <td class="px-6 py-3.5 text-neutral-400">{{ $inv->issue_date?->format('Y.m.d') }}</td>
                            <td class="px-6 py-3.5"><span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $inv->status === 'canceled' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $inv->status_label }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="mt-6">{{ $invoices->links() }}</div>

{{-- 상세 팝업 --}}
@foreach ($invoices as $inv)
    <x-detail-modal :id="$inv->id">
        <x-slot:actions>
            <button type="button" onclick="printFrame('{{ route('portal.hq.invoices.print', ['invoice' => $inv, 'print' => 1]) }}')"
                    class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm shadow">🖨️ 인쇄</button>
        </x-slot:actions>
        @include('portal.partials.tax-invoice-document', ['invoice' => $inv])
    </x-detail-modal>
@endforeach
</div>
@endsection
