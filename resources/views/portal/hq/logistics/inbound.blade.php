@extends('portal.layout')
@section('title', '입고관리')

@section('content')
<div x-data="{ manualOpen: false }">
<x-wms.page-head title="물류관리 · 입고관리" subtitle="공급처 거래명세서를 입고 처리하면 본사 재고가 증가합니다. 수동 입고도 가능합니다." icon="📥">
    <x-slot:actions>
        <button type="button" @click="manualOpen = true" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 수동 입고</button>
    </x-slot:actions>
</x-wms.page-head>

{{-- 상태 필터 --}}
<div class="flex gap-1.5 mb-5">
    @foreach (['all'=>'전체','pending'=>'입고 전','done'=>'입고완료'] as $k => $label)
        <a href="{{ route('portal.hq.logistics.inbound', ['status' => $k]) }}"
           class="px-4 py-2 rounded-xl text-sm font-bold transition {{ $filter === $k ? 'bg-mango-500 text-white' : 'bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50' }}">{{ $label }}</a>
    @endforeach
</div>

<x-wms.panel>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-5 py-3">명세서번호</th>
                <th class="text-left font-semibold px-5 py-3">공급처</th>
                <th class="text-right font-semibold px-5 py-3">품목수</th>
                <th class="text-right font-semibold px-5 py-3">공급가</th>
                <th class="text-left font-semibold px-5 py-3">입고상태</th>
                <th class="text-right font-semibold px-5 py-3 w-32">처리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($statements as $s)
                <tr class="hover:bg-neutral-50">
                    <td class="px-5 py-3 font-bold text-neutral-900">{{ $s->statement_no }}</td>
                    <td class="px-5 py-3 text-neutral-700">{{ $s->supplier_name ?: optional($s->supplier)->name ?: '-' }}</td>
                    <td class="px-5 py-3 text-right text-neutral-500">{{ number_format($s->item_count) }}</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ number_format($s->supply_total) }}원</td>
                    <td class="px-5 py-3">
                        @if ($s->received_at)
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">✔ 입고완료</span>
                            <span class="block text-[11px] text-neutral-400 mt-0.5">{{ $s->received_at->format('Y.m.d H:i') }}</span>
                        @else
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">입고 전</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        @unless ($s->received_at)
                            <form method="POST" action="{{ route('portal.hq.logistics.inbound_receive', $s) }}"
                                  onsubmit="return confirm('명세서 {{ $s->statement_no }}({{ number_format($s->item_count) }}품목)를 입고 처리합니다.\n본사 재고가 증가합니다. 진행할까요?')">
                                @csrf
                                <button class="rounded-lg bg-mango-500 hover:bg-mango-600 text-white font-bold px-3 py-1.5 text-xs">📥 입고 처리</button>
                            </form>
                        @else
                            <span class="text-xs text-neutral-300">완료</span>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-12 text-center text-neutral-400">공급처 거래명세서가 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($statements->hasPages())
        <div class="px-5 py-3 border-t border-neutral-100">{{ $statements->links() }}</div>
    @endif
</x-wms.panel>

{{-- 수동 입고 모달 --}}
<div x-show="manualOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4 overflow-y-auto" @click.self="manualOpen = false">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto my-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900">수동 입고</h2>
            <button @click="manualOpen = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" action="{{ route('portal.hq.logistics.inbound_manual') }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">품목 *</label>
                <select name="supply_product_id" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    <option value="">품목 선택…</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">수량 *</label>
                <input type="number" name="qty" min="1" max="1000000" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="입고 수량">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">메모 <span class="text-neutral-400 font-normal">(선택)</span></label>
                <input type="text" name="note" maxlength="200" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="예: 초기 재고 등록">
            </div>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition">입고</button>
                <button type="button" @click="manualOpen = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
