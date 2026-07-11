@extends('portal.layout')
@section('title', '매장 원장(정산)')

@section('content')
<x-wms.page-head title="매장 원장(정산)" subtitle="매장별 예치금 잔액·미수금을 관리합니다. 발주는 차감, 입금은 충전됩니다." icon="📒">
    <x-slot:actions>
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ $q }}" placeholder="매장 검색" class="rounded-xl border-neutral-200 text-sm py-2">
            <button class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-3.5 py-2 text-sm">검색</button>
        </form>
    </x-slot:actions>
</x-wms.page-head>

<div class="grid sm:grid-cols-2 gap-4 mb-6">
    <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white p-6">
        <p class="text-white/80 font-semibold text-sm">예치금 잔액 합계</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totals['prepaid']) }}<span class="text-lg">원</span></p>
    </div>
    <div class="rounded-2xl bg-gradient-to-br from-rose-500 to-rose-600 text-white p-6">
        <p class="text-white/80 font-semibold text-sm">미수금 합계</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totals['unpaid']) }}<span class="text-lg">원</span></p>
    </div>
</div>

<x-wms.panel>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-5 py-3">매장</th>
                    <th class="text-left font-semibold px-5 py-3">정산방식</th>
                    <th class="text-left font-semibold px-5 py-3 hidden md:table-cell">가상계좌</th>
                    <th class="text-right font-semibold px-5 py-3">잔액</th>
                    <th class="text-right font-semibold px-5 py-3">관리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($stores as $s)
                    <tr class="hover:bg-mango-50/40">
                        <td class="px-5 py-3.5 font-bold text-neutral-900">{{ $s->name }}</td>
                        <td class="px-5 py-3.5">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $s->settlement_type === 'prepaid' ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-500' }}">{{ $s->settlement_label }}</span>
                        </td>
                        <td class="px-5 py-3.5 hidden md:table-cell text-neutral-500 font-mono text-xs">{{ $s->virtual_account ?: '-' }}</td>
                        <td class="px-5 py-3.5 text-right font-black whitespace-nowrap {{ $s->ledger_balance < 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                            {{ number_format($s->ledger_balance) }}원
                            <span class="block text-[11px] font-bold {{ $s->ledger_balance < 0 ? 'text-rose-400' : 'text-emerald-400' }}">{{ $s->ledger_balance < 0 ? '미수' : '예치' }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-right"><a href="{{ route('portal.hq.store_ledger.show', $s) }}" class="text-xs font-bold text-mango-600 hover:text-mango-700">원장 보기</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-neutral-400">매장이 없습니다.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-wms.panel>

<div class="mt-5">{{ $stores->links() }}</div>
@endsection
