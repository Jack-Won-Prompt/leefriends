@extends('portal.layout')
@section('title', '재고관리')

@section('content')
<div x-data="{
        open: false,
        form: { id: null, name: '', unit: '', qty: 0 },
        openEdit(p) { this.form = { id: p.id, name: p.name, unit: p.unit || '', qty: p.qty }; this.open = true; },
     }">
<x-wms.page-head title="물류관리 · 재고관리" subtitle="품목별 본사 재고(실물·예약·가용)를 확인하고 실사 수량을 입력·수정합니다." icon="📊">
    <x-slot:actions>
        <form method="POST" action="{{ route('portal.hq.logistics.inventory_seed') }}"
              onsubmit="return confirm('재고가 없는 품목(미등록·실물 0)에 기본재고 10개를 설정합니다.\n이력이 기록됩니다. 진행할까요?')">
            @csrf
            <button type="submit" class="inline-flex items-center gap-1 rounded-xl bg-neutral-800 hover:bg-neutral-900 text-white font-bold px-4 py-2 text-sm transition">📦 기본재고 셋팅</button>
        </form>
    </x-slot:actions>
</x-wms.page-head>

{{-- 검색/필터 --}}
<form method="GET" class="flex flex-wrap items-end gap-3 mb-5">
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">품목 검색</label>
        <input type="text" name="q" value="{{ $keyword }}" placeholder="품목명·코드" class="rounded-xl border-neutral-200 text-sm py-2 min-w-[14rem]">
    </div>
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">범위</label>
        <select name="only" class="rounded-xl border-neutral-200 text-sm py-2">
            <option value="all" @selected($only==='all')>전체 품목</option>
            <option value="managed" @selected($only==='managed')>재고 관리중</option>
            <option value="shortage" @selected($only==='shortage')>재고 없음(가용≤0)</option>
        </select>
    </div>
    <button type="submit" class="rounded-xl bg-neutral-800 hover:bg-neutral-900 text-white font-bold px-4 py-2.5 text-sm transition">조회</button>
</form>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2">
        <x-wms.panel>
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-5 py-3">품목</th>
                        <th class="text-right font-semibold px-5 py-3">실물</th>
                        <th class="text-right font-semibold px-5 py-3">예약</th>
                        <th class="text-right font-semibold px-5 py-3">가용</th>
                        <th class="text-right font-semibold px-5 py-3 w-24">수정</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @forelse ($rows as $p)
                        @php $managed = ! is_null($p->inv_id); $avail = $managed ? ((int)$p->qty - (int)$p->reserved_qty) : null; @endphp
                        <tr class="hover:bg-neutral-50">
                            <td class="px-5 py-3">
                                <span class="font-bold text-neutral-900">{{ $p->name }}</span>
                                <span class="block text-xs text-neutral-400">{{ $p->code }} · {{ $p->unit }}</span>
                            </td>
                            @if ($managed)
                                <td class="px-5 py-3 text-right tabular-nums">{{ number_format($p->qty) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums text-amber-600">{{ number_format($p->reserved_qty) }}</td>
                                <td class="px-5 py-3 text-right tabular-nums font-bold {{ $avail <= 0 ? 'text-rose-500' : 'text-emerald-600' }}">
                                    {{ number_format($avail) }}@if ($avail <= 0)<span class="block text-[11px] font-normal">재고없음</span>@endif
                                </td>
                            @else
                                <td class="px-5 py-3 text-right text-neutral-300" colspan="3">재고 미설정</td>
                            @endif
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                @if (! $managed || (int) $p->qty <= 0)
                                    <form method="POST" action="{{ route('portal.hq.logistics.inventory_seed_one', $p->id) }}" class="inline"
                                          onsubmit="return confirm('{{ $p->name }} 기본재고 10개를 설정합니다. 진행할까요?')">
                                        @csrf
                                        <button class="text-emerald-600 hover:underline text-xs font-bold mr-2">기본재고 셋팅</button>
                                    </form>
                                @endif
                                <button type="button" @click="openEdit({ id: {{ $p->id }}, name: {{ \Illuminate\Support\Js::from($p->name) }}, unit: {{ \Illuminate\Support\Js::from($p->unit) }}, qty: {{ (int) ($p->qty ?? 0) }} })"
                                        class="text-mango-600 hover:underline text-xs font-bold">수량 수정</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-neutral-400">품목이 없습니다.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($rows->hasPages())
                <div class="px-5 py-3 border-t border-neutral-100">{{ $rows->links() }}</div>
            @endif
        </x-wms.panel>
    </div>

    {{-- 최근 이동 이력 --}}
    <div>
        <x-wms.panel>
            <div class="px-5 py-3 border-b border-neutral-100 text-sm font-bold text-neutral-700">최근 재고 이동</div>
            <div class="divide-y divide-neutral-100 max-h-[32rem] overflow-y-auto">
                @forelse ($recent as $m)
                    @php $tc = ['inbound'=>'text-emerald-600','ship'=>'text-rose-500','reserve'=>'text-amber-600','release'=>'text-sky-600','adjust'=>'text-neutral-600'][$m->type] ?? 'text-neutral-600'; @endphp
                    <div class="px-5 py-2.5 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-neutral-800">{{ $m->product_name }}</span>
                            <span class="font-bold {{ $tc }}">{{ $m->typeLabel() }}</span>
                        </div>
                        <div class="flex items-center justify-between text-neutral-400 mt-0.5">
                            <span>실물 {{ $m->qty_delta >= 0 ? '+' : '' }}{{ $m->qty_delta }} · 예약 {{ $m->reserved_delta >= 0 ? '+' : '' }}{{ $m->reserved_delta }}</span>
                            <span>{{ $m->created_at->format('m/d H:i') }}</span>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-10 text-center text-neutral-400 text-sm">이동 이력이 없습니다.</p>
                @endforelse
            </div>
        </x-wms.panel>
    </div>
</div>

{{-- 수량 수정 팝업 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4 overflow-y-auto" @click.self="open = false">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto my-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900">재고 수량 수정</h2>
            <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" action="{{ route('portal.hq.logistics.inventory_adjust') }}" class="p-5 space-y-4">
            @csrf
            <input type="hidden" name="supply_product_id" :value="form.id">
            <p class="text-sm font-bold text-neutral-800" x-text="form.name"></p>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">실물 재고 수량 *</label>
                <input type="number" name="qty" x-model.number="form.qty" min="0" max="1000000" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                <p class="text-[11px] text-neutral-400 mt-1">실사 기준 실물 수량을 입력하면 조정 이력이 남습니다. (예약분은 그대로 유지)</p>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">메모 <span class="text-neutral-400 font-normal">(선택)</span></label>
                <input type="text" name="note" maxlength="200" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="예: 월말 실사">
            </div>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition">저장</button>
                <button type="button" @click="open = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
