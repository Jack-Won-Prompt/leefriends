@extends('portal.layout')
@section('title', '거래명세서(수취)')

@section('content')
<x-wms.page-head title="거래명세서(수취)" subtitle="본사가 발송한 거래명세서를 확인하고 PDF로 볼 수 있습니다." icon="🧾" />

<x-wms.panel>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-6 py-3">발행일자</th>
                <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">품목수</th>
                <th class="text-right font-semibold px-6 py-3">금액</th>
                <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">수신일시</th>
                <th class="text-right font-semibold px-6 py-3 w-28">명세서</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($statements as $s)
                <tr class="hover:bg-mango-50/40">
                    <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $s->issueDate()->format('Y.m.d') }}</td>
                    <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ number_format($s->item_count) }}</td>
                    <td class="px-6 py-3.5 text-right font-bold text-mango-700 tabular-nums">{{ number_format($s->total) }}원</td>
                    <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ optional($s->sent_at)->format('Y.m.d H:i') }}</td>
                    <td class="px-6 py-3.5 text-right">
                        <a href="{{ route('portal.store.statements.pdf', $s) }}" target="_blank"
                           class="inline-flex items-center gap-1 rounded-lg bg-mango-500 hover:bg-mango-600 text-white font-bold px-3 py-1.5 text-xs transition">🧾 PDF 보기</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-neutral-400">수취한 거래명세서가 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($statements->hasPages())
        <div class="px-6 py-3 border-t border-neutral-100">{{ $statements->links() }}</div>
    @endif
</x-wms.panel>
@endsection
