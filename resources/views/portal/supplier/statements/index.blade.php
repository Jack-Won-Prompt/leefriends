@extends('portal.layout')
@section('title', '거래명세서 이력')

@section('content')
<x-wms.page-head title="거래명세서 이력" subtitle="거래명세서를 선택해 세금계산서를 발행합니다 (여러 건 합산 가능)" icon="📄">
    <x-slot:actions>
        <a href="{{ route('portal.supplier.statements.create') }}" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2.5 text-sm transition">+ 거래명세서 작성</a>
    </x-slot:actions>
</x-wms.page-head>

<div x-data="{
        open: null,
        picked: [],
        totals: {{ \Illuminate\Support\Js::from($statements->where('tax_invoice_id', null)->mapWithKeys(fn ($s) => [$s->id => (int) $s->total])) }},
        get total() { return this.picked.reduce((s, id) => s + (this.totals[id] || 0), 0); },
        toggleAll(e) { this.picked = e.target.checked ? Object.keys(this.totals).map(Number) : []; }
     }">
<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    @if ($statements->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">작성한 거래명세서가 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="px-6 py-3 w-10">
                            <input type="checkbox" @change="toggleAll($event)" class="rounded text-mango-500 focus:ring-mango-400">
                        </th>
                        <th class="text-left font-semibold px-4 py-3">명세서번호</th>
                        <th class="text-left font-semibold px-4 py-3">작성일</th>
                        <th class="text-right font-semibold px-4 py-3">품목</th>
                        <th class="text-right font-semibold px-4 py-3">공급가액</th>
                        <th class="text-right font-semibold px-6 py-3">합계</th>
                        <th class="text-left font-semibold px-4 py-3">거래명세서 본사 전송</th>
                        <th class="text-left font-semibold px-4 py-3">세금계산서</th>
                        <th class="text-right font-semibold px-6 py-3 w-28">관리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($statements as $s)
                        <tr class="hover:bg-mango-50/40 transition" :class="picked.includes({{ $s->id }}) ? 'bg-mango-50/60' : ''">
                            <td class="px-6 py-3.5">
                                @unless ($s->tax_invoice_id)
                                    <input type="checkbox" value="{{ $s->id }}" x-model.number="picked" class="rounded text-mango-500 focus:ring-mango-400">
                                @endunless
                            </td>
                            <td class="px-4 py-3.5 font-bold text-mango-700">
                                <button type="button" @click="open = {{ $s->id }}" class="hover:underline">{{ $s->statement_no }}</button>
                            </td>
                            <td class="px-4 py-3.5 text-neutral-500">{{ $s->created_at->format('Y.m.d H:i') }}</td>
                            <td class="px-4 py-3.5 text-right text-neutral-500">{{ number_format($s->item_count) }}건</td>
                            <td class="px-4 py-3.5 text-right">{{ number_format($s->supply_total) }}원</td>
                            <td class="px-6 py-3.5 text-right font-black text-mango-700">{{ number_format($s->total) }}원</td>
                            <td class="px-4 py-3.5">
                                @if ($s->emailed_at)
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-sky-100 text-sky-700">전송됨{{ $s->email_count > 1 ? ' '.$s->email_count : '' }}</span>
                                    <span class="block text-[11px] text-neutral-400 mt-0.5">{{ $s->emailed_at->format('Y.m.d H:i') }}</span>
                                @else
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-neutral-100 text-neutral-400">미전송</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                @if ($s->tax_invoice_id)
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">발행완료</span>
                                    <span class="block text-[11px] text-neutral-400 mt-0.5">{{ optional($s->taxInvoice)->invoice_no }}</span>
                                @else
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-neutral-100 text-neutral-400">미발행</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                <button type="button" @click="open = {{ $s->id }}" class="text-xs font-bold text-neutral-500 hover:text-mango-600 mr-3">상세</button>
                                @unless ($s->tax_invoice_id)
                                    <form method="POST" action="{{ route('portal.supplier.statements.destroy', $s) }}" class="inline"
                                          onsubmit="return confirm('이 거래명세서를 삭제할까요?')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs font-bold text-rose-500 hover:text-rose-600">삭제</button>
                                    </form>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- 선택 발행 바 --}}
<div class="sticky bottom-4 mt-6 rounded-2xl bg-neutral-900 text-white p-5 flex items-center justify-between shadow-lg" x-show="picked.length > 0" x-cloak>
    <div>
        <span class="text-white/60 text-sm">거래명세서 <span class="font-bold text-white" x-text="picked.length"></span>건 선택 · 합계</span>
        <span class="ml-2 text-2xl font-black text-mango-300"><span x-text="total.toLocaleString()"></span>원</span>
        <span class="block text-xs text-white/40 mt-0.5">선택한 거래명세서를 합산하여 1장으로 발행합니다. (과세/면세는 자동 분리)</span>
    </div>
    <form method="POST" action="{{ route('portal.supplier.statements.issue_bulk') }}"
          onsubmit="return confirm('선택한 거래명세서로 세금계산서를 발행합니다. (본사 청구)\n진행하시겠습니까?')">
        @csrf
        <template x-for="id in picked" :key="id">
            <input type="hidden" name="statement_ids[]" :value="id">
        </template>
        <button type="submit" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-6 py-3 text-sm transition">🧾 세금계산서 발행</button>
    </form>
</div>

{{-- 상세 팝업 --}}
@foreach ($statements as $s)
    <x-detail-modal :id="$s->id">
        <x-slot:actions>
            <a href="{{ route('portal.supplier.statements.pdf', $s) }}" target="_blank" class="rounded-xl bg-white/90 hover:bg-white text-neutral-700 font-bold px-4 py-2 text-sm shadow">PDF</a>
            <form method="POST" action="{{ route('portal.supplier.statements.email', $s) }}"
                  onsubmit="return confirm('이 거래명세서 PDF를 본사로 이메일 전송합니다.\n진행하시겠습니까?')">
                @csrf
                <button type="submit" class="rounded-xl bg-sky-500 hover:bg-sky-600 text-white font-bold px-4 py-2 text-sm shadow">📧 {{ $s->emailed_at ? '본사 재전송' : '본사 전송' }}</button>
            </form>
            @unless ($s->tax_invoice_id)
                <form method="POST" action="{{ route('portal.supplier.statements.issue', $s) }}"
                      onsubmit="return confirm('이 거래명세서로 세금계산서를 발행합니다. (본사 청구)\n진행하시겠습니까?')">
                    @csrf
                    <button type="submit" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm shadow">🧾 세금계산서 발행</button>
                </form>
            @endunless
            <button type="button" onclick="window.print()" class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm shadow">🖨️ 인쇄</button>
        </x-slot:actions>
        @include('portal.partials.supplier-statement-document', ['statement' => $s])
    </x-detail-modal>
@endforeach
</div>

<div class="mt-6">{{ $statements->links() }}</div>
@endsection
