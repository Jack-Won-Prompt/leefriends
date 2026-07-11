@extends('portal.layout')
@section('title', $store->name . ' 원장')

@section('content')
<a href="{{ route('portal.hq.store_ledger.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600 mb-5">← 매장 원장</a>

<x-wms.page-head :title="$store->name . ' 원장'" :subtitle="$store->settlement_label . ($store->virtual_account ? ' · 가상계좌 ' . $store->virtual_account : '')" icon="📒" />

@if ($errors->any())
    <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-5 py-3 text-sm">{{ $errors->first() }}</div>
@endif

<div class="grid lg:grid-cols-3 gap-4 mb-6">
    {{-- 잔액 --}}
    <div class="rounded-2xl p-6 text-white {{ $store->ledger_balance < 0 ? 'bg-gradient-to-br from-rose-500 to-rose-600' : 'bg-gradient-to-br from-emerald-500 to-emerald-600' }}">
        <p class="text-white/80 font-semibold text-sm">{{ $store->ledger_balance < 0 ? '미수금' : '예치 잔액' }}</p>
        <p class="text-3xl font-black mt-1">{{ number_format(abs($store->ledger_balance)) }}<span class="text-lg">원</span></p>
    </div>

    {{-- 충전 / 조정 --}}
    <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-5 space-y-3">
        <form method="POST" action="{{ route('portal.hq.store_ledger.charge', $store) }}" class="flex items-end gap-2">
            @csrf
            <div class="flex-1">
                <label class="block text-xs font-bold text-neutral-500 mb-1">충전 금액</label>
                <input type="number" name="amount" min="1" required class="w-full rounded-xl border-neutral-200 text-sm py-2" placeholder="원">
            </div>
            <input type="text" name="memo" class="w-28 rounded-xl border-neutral-200 text-sm py-2" placeholder="메모">
            <button class="rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-4 py-2 text-sm">충전</button>
        </form>
        <form method="POST" action="{{ route('portal.hq.store_ledger.adjust', $store) }}" class="flex items-end gap-2">
            @csrf
            <div class="flex-1">
                <label class="block text-xs font-bold text-neutral-500 mb-1">잔액 조정(목표)</label>
                <input type="number" name="balance" required value="{{ $store->ledger_balance }}" class="w-full rounded-xl border-neutral-200 text-sm py-2">
            </div>
            <input type="text" name="memo" class="w-28 rounded-xl border-neutral-200 text-sm py-2" placeholder="메모">
            <button class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2 text-sm">조정</button>
        </form>
    </div>

    {{-- 정산 설정 --}}
    <form method="POST" action="{{ route('portal.hq.store_ledger.settings', $store) }}" class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-5 space-y-3">
        @csrf @method('PATCH')
        <div>
            <label class="block text-xs font-bold text-neutral-500 mb-1">정산 방식</label>
            <select name="settlement_type" class="w-full rounded-xl border-neutral-200 text-sm py-2">
                @foreach (\App\Models\Store::SETTLEMENT_TYPES as $k => $v)<option value="{{ $k }}" @selected($store->settlement_type === $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-bold text-neutral-500 mb-1">가상계좌 / 입금 식별</label>
            <input type="text" name="virtual_account" value="{{ $store->virtual_account }}" class="w-full rounded-xl border-neutral-200 text-sm py-2" placeholder="예: 12345678901234">
        </div>
        <button class="w-full rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm">설정 저장</button>
    </form>
</div>

<x-wms.panel>
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">거래 원장</div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">일시</th>
                    <th class="text-left font-semibold px-6 py-3">구분</th>
                    <th class="text-left font-semibold px-6 py-3">내용</th>
                    <th class="text-right font-semibold px-6 py-3">증감</th>
                    <th class="text-right font-semibold px-6 py-3">잔액</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($entries as $e)
                    <tr class="hover:bg-mango-50/40">
                        <td class="px-6 py-3 text-neutral-500 whitespace-nowrap">{{ $e->created_at->format('Y-m-d H:i') }}</td>
                        <td class="px-6 py-3">
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $e->amount < 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $e->type_label }}</span>
                        </td>
                        <td class="px-6 py-3 text-neutral-600">{{ $e->memo }}@if ($e->creator)<span class="text-xs text-neutral-400 ml-1">({{ $e->creator->name }})</span>@endif</td>
                        <td class="px-6 py-3 text-right font-black whitespace-nowrap {{ $e->amount < 0 ? 'text-rose-600' : 'text-emerald-600' }}">{{ $e->amount > 0 ? '+' : '' }}{{ number_format($e->amount) }}원</td>
                        <td class="px-6 py-3 text-right font-bold text-neutral-700 whitespace-nowrap">{{ number_format($e->balance_after) }}원</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-neutral-400">원장 내역이 없습니다.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-wms.panel>

<div class="mt-5">{{ $entries->links() }}</div>
@endsection
