@extends('portal.layout')
@section('title', '거래명세서 이력')

@section('content')
<x-wms.page-head title="거래명세서 이력" subtitle="작성한 거래명세서를 선택해 세금계산서를 발행합니다" icon="📄">
    <x-slot:actions>
        <a href="{{ route('portal.supplier.statements.create') }}" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2.5 text-sm transition">+ 거래명세서 작성</a>
    </x-slot:actions>
</x-wms.page-head>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    @if ($statements->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">작성한 거래명세서가 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">명세서번호</th>
                        <th class="text-left font-semibold px-6 py-3">작성일</th>
                        <th class="text-right font-semibold px-6 py-3">품목</th>
                        <th class="text-right font-semibold px-6 py-3">공급가액</th>
                        <th class="text-right font-semibold px-6 py-3">합계</th>
                        <th class="text-left font-semibold px-6 py-3">세금계산서</th>
                        <th class="text-right font-semibold px-6 py-3 w-44">관리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($statements as $s)
                        <tr class="hover:bg-mango-50/40 transition">
                            <td class="px-6 py-3.5 font-bold text-neutral-900">
                                <a href="{{ route('portal.supplier.statements.show', $s) }}" class="hover:text-mango-600">{{ $s->statement_no }}</a>
                            </td>
                            <td class="px-6 py-3.5 text-neutral-500">{{ $s->created_at->format('Y.m.d H:i') }}</td>
                            <td class="px-6 py-3.5 text-right text-neutral-500">{{ number_format($s->item_count) }}건</td>
                            <td class="px-6 py-3.5 text-right">{{ number_format($s->supply_total) }}원</td>
                            <td class="px-6 py-3.5 text-right font-black text-mango-700">{{ number_format($s->total) }}원</td>
                            <td class="px-6 py-3.5">
                                @if ($s->tax_invoice_id)
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">발행완료</span>
                                    <span class="block text-[11px] text-neutral-400 mt-0.5">{{ optional($s->taxInvoice)->invoice_no }}</span>
                                @else
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-neutral-100 text-neutral-400">미발행</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                @if (! $s->tax_invoice_id)
                                    <form method="POST" action="{{ route('portal.supplier.statements.issue', $s) }}" class="inline"
                                          onsubmit="return confirm('이 거래명세서로 세금계산서를 발행합니다. (본사 청구)\n진행하시겠습니까?')">
                                        @csrf
                                        <button class="text-xs font-bold text-mango-600 hover:text-mango-700 mr-3">🧾 세금계산서 발행</button>
                                    </form>
                                    <form method="POST" action="{{ route('portal.supplier.statements.destroy', $s) }}" class="inline"
                                          onsubmit="return confirm('이 거래명세서를 삭제할까요? 품목이 다시 미청구 상태로 돌아갑니다.')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs font-bold text-rose-500 hover:text-rose-600">삭제</button>
                                    </form>
                                @else
                                    <a href="{{ route('portal.supplier.statements.show', $s) }}" class="text-xs font-bold text-neutral-500 hover:text-mango-600">상세</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="mt-6">{{ $statements->links() }}</div>
@endsection
