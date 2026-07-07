@extends('portal.layout')
@section('title', '거래명세서')

@section('content')
<div x-data="{ open: null }">
<x-wms.page-head title="거래명세서" subtitle="작성·발송한 거래명세서 이력입니다. PDF 재보기·재전송할 수 있습니다." icon="🧾">
    <x-slot:actions>
        <a href="{{ route('portal.hq.statements.create') }}" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 거래명세서 작성</a>
    </x-slot:actions>
</x-wms.page-head>

<x-date-filter :from="$from" :to="$to" label="발송일 기간" />

<x-wms.toolbar :count="$statements->total()" label="발송 이력" />

<x-wms.panel>
    @if ($statements->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">발송한 거래명세서가 없습니다. «거래명세서 작성»으로 발행해 보세요.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">거래명세서 매장 전송</th>
                        <th class="text-left font-semibold px-6 py-3">매장</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">수신 이메일</th>
                        <th class="text-right font-semibold px-6 py-3">품목</th>
                        <th class="text-right font-semibold px-6 py-3">합계</th>
                        <th class="text-left font-semibold px-6 py-3 hidden lg:table-cell">발송자</th>
                        <th class="text-left font-semibold px-6 py-3">매장 확인</th>
                        <th class="text-left font-semibold px-6 py-3">세금계산서</th>
                        <th class="text-right font-semibold px-6 py-3 w-40">관리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($statements as $s)
                        <tr class="hover:bg-mango-50/40 transition">
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-sky-100 text-sky-700">전송됨@if ($s->resend_count > 0) (재전송 {{ $s->resend_count }})@endif</span>
                                <span class="block text-[11px] text-neutral-400 mt-0.5">{{ $s->sent_at->format('Y.m.d H:i') }}</span>
                            </td>
                            <td class="px-6 py-3.5 font-bold text-mango-700">
                                <button type="button" @click="open = {{ $s->id }}" class="hover:underline">{{ $s->store_name }}</button>
                                @if ($s->order)<span class="block text-[11px] text-sky-600 font-bold mt-0.5">발주 {{ $s->order->order_no }}</span>@endif
                            </td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-500">{{ $s->email ?: '-' }}</td>
                            <td class="px-6 py-3.5 text-right text-neutral-500">{{ number_format($s->item_count) }}건</td>
                            <td class="px-6 py-3.5 text-right font-black text-mango-700">{{ number_format($s->total) }}원</td>
                            <td class="px-6 py-3.5 hidden lg:table-cell text-neutral-500">{{ optional($s->sender)->name ?? '본사' }}</td>
                            @php $rc = ['pending'=>'bg-neutral-100 text-neutral-400','viewed'=>'bg-amber-100 text-amber-700','confirmed'=>'bg-emerald-100 text-emerald-700'][$s->receiptStatus()]; @endphp
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $rc }}">{{ $s->receiptLabel() }}</span>
                                @if ($s->confirmed_at)<span class="block text-[11px] text-neutral-400 mt-0.5">{{ $s->confirmed_at->format('m.d H:i') }}</span>@endif
                            </td>
                            <td class="px-6 py-3.5 whitespace-nowrap">
                                @if ($s->tax_invoice_id)
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">발행완료</span>
                                    <span class="block text-[11px] text-neutral-400 mt-0.5">{{ optional($s->taxInvoice)->invoice_no }}</span>
                                @elseif (! optional($s->store)->biz_no)
                                    <span class="text-[11px] text-amber-600" title="매장 사업자등록번호 필요">사업자정보 없음</span>
                                @else
                                    <form method="POST" action="{{ route('portal.hq.tax_invoices.issue_statement', $s) }}"
                                          onsubmit="return confirm('이 거래명세서로 세금계산서를 발행합니다.\n수신: {{ $s->store_name }} ({{ $s->email }})\n진행하시겠습니까?')">
                                        @csrf
                                        <button type="submit" class="text-xs font-bold text-mango-600 hover:text-mango-700">🧾 발행</button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                <button type="button" @click="open = {{ $s->id }}" class="text-xs font-bold text-neutral-500 hover:text-mango-600 mr-3">상세</button>
                                <a href="{{ route('portal.hq.statements.pdf', $s) }}" target="_blank"
                                   class="text-xs font-bold text-neutral-700 hover:text-mango-600 mr-3">PDF</a>
                                <form method="POST" action="{{ route('portal.hq.statements.resend', $s) }}" class="inline"
                                      onsubmit="return confirm('«{{ $s->store_name }}»({{ $s->email }})로 거래명세서를 재전송할까요?')">
                                    @csrf
                                    <button class="text-xs font-bold text-mango-600 hover:text-mango-700" @disabled(! $s->email)>재전송</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-wms.panel>

@if ($statements->hasPages())
    <div class="mt-5">{{ $statements->links() }}</div>
@endif

{{-- 상세 팝업 --}}
@foreach ($statements as $s)
    <x-detail-modal :id="$s->id">
        <x-slot:actions>
            <a href="{{ route('portal.hq.statements.pdf', $s) }}" target="_blank" class="rounded-xl bg-white/90 hover:bg-white text-neutral-700 font-bold px-4 py-2 text-sm shadow">PDF</a>
            @if (! $s->tax_invoice_id && optional($s->store)->biz_no)
                <form method="POST" action="{{ route('portal.hq.tax_invoices.issue_statement', $s) }}"
                      onsubmit="return confirm('이 거래명세서로 세금계산서를 발행합니다.\n수신: {{ $s->store_name }} ({{ $s->email }})\n진행하시겠습니까?')">
                    @csrf
                    <button type="submit" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm shadow">🧾 세금계산서 발행</button>
                </form>
            @endif
            <button type="button" onclick="printFrame('{{ route('portal.hq.statements.print', ['statement' => $s, 'print' => 1]) }}')" class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm shadow">🖨️ 인쇄</button>
        </x-slot:actions>
        @include('portal.partials.hq-statement-document', ['statement' => $s])
    </x-detail-modal>
@endforeach
</div>
@endsection
