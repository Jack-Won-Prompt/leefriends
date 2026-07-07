@extends('portal.layout')
@section('title', '거래명세서(수취)')

@section('content')
<x-wms.page-head title="거래명세서(수취)" subtitle="본사가 발송한 거래명세서를 확인하고 PDF로 볼 수 있습니다." icon="🧾" />

<x-date-filter :from="$from" :to="$to" label="발송일 기간" />

<x-wms.panel>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-6 py-3">발행일자</th>
                <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">품목수</th>
                <th class="text-right font-semibold px-6 py-3">금액</th>
                <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">수신일시</th>
                <th class="text-left font-semibold px-6 py-3">상태</th>
                <th class="text-right font-semibold px-6 py-3 w-44">명세서</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($statements as $s)
                @php $rc = ['pending'=>'bg-neutral-100 text-neutral-500','viewed'=>'bg-amber-100 text-amber-700','confirmed'=>'bg-emerald-100 text-emerald-700'][$s->receiptStatus()]; @endphp
                <tr class="hover:bg-mango-50/40">
                    <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $s->issueDate()->format('Y.m.d') }}</td>
                    <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ number_format($s->item_count) }}</td>
                    <td class="px-6 py-3.5 text-right font-bold text-mango-700 tabular-nums">{{ number_format($s->total) }}원</td>
                    <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ optional($s->sent_at)->format('Y.m.d H:i') }}</td>
                    <td class="px-6 py-3.5">
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $rc }}">{{ $s->receiptLabel() }}</span>
                        @if ($s->confirmed_at)<span class="block text-[11px] text-neutral-400 mt-0.5">{{ $s->confirmed_at->format('m.d H:i') }}</span>@endif
                    </td>
                    <td class="px-6 py-3.5 text-right whitespace-nowrap">
                        <a href="{{ route('portal.store.statements.pdf', $s) }}" target="_blank"
                           class="inline-flex items-center gap-1 rounded-lg bg-mango-500 hover:bg-mango-600 text-white font-bold px-3 py-1.5 text-xs transition">🧾 PDF 보기</a>
                        @unless ($s->confirmed_at)
                            <form method="POST" action="{{ route('portal.store.statements.confirm', $s) }}" class="inline"
                                  onsubmit="return confirm('이 거래명세서를 확인 처리할까요? 본사에 통보됩니다.')">
                                @csrf
                                <button class="inline-flex items-center gap-1 rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-3 py-1.5 text-xs transition ml-1">✔ 확인</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-neutral-400">수취한 거래명세서가 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($statements->hasPages())
        <div class="px-6 py-3 border-t border-neutral-100">{{ $statements->links() }}</div>
    @endif
</x-wms.panel>
@endsection
