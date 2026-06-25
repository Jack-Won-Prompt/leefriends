@extends('portal.layout')
@section('title', '거래명세서 작성')

@section('content')
<div x-data="stmtBuilder({{ \Illuminate\Support\Js::from($catalog) }})">
<x-wms.page-head title="거래명세서 작성" subtitle="공급 품목과 수량을 선택하면 금액이 자동 계산됩니다. 작성 후 세금계산서를 발행하세요." icon="🧾" />

@if (count($catalog) === 0)
    <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-12 text-center text-neutral-400">
        등록된 공급 품목이 없습니다. 물품 관리에서 품목을 먼저 등록하세요.
    </div>
@else
<form method="POST" action="{{ route('portal.supplier.statements.store') }}">
    @csrf
    <template x-for="(l, i) in lines" :key="l.id">
        <span>
            <input type="hidden" :name="`items[${i}][product_id]`" :value="l.id">
            <input type="hidden" :name="`items[${i}][qty]`" :value="l.qty">
        </span>
    </template>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- 좌: 품목 카탈로그 --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
                <div class="p-3 border-b border-neutral-100">
                    <input type="text" x-model="search" placeholder="🔍 품목명 · 코드 검색"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
                <div class="max-h-[460px] overflow-y-auto divide-y divide-neutral-50">
                    <template x-for="p in filtered()" :key="p.id">
                        <div class="flex items-center gap-3 px-5 py-3">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-neutral-900 text-sm" x-text="p.name"></p>
                                <p class="text-xs text-neutral-400"><span x-text="p.code"></span> · <span x-text="p.category"></span> · 공급단가 <span class="font-bold text-mango-700" x-text="p.price.toLocaleString() + '원'"></span></p>
                            </div>
                            <button type="button" @click="add(p)"
                                    class="shrink-0 rounded-lg bg-mango-500 hover:bg-mango-600 text-white font-bold px-3 py-1.5 text-sm"
                                    x-text="inCart(p.id) ? '추가됨 +' : '추가'"></button>
                        </div>
                    </template>
                    <p x-show="filtered().length === 0" class="px-5 py-10 text-center text-sm text-neutral-400">검색 결과가 없습니다.</p>
                </div>
            </div>
        </div>

        {{-- 우: 선택 품목 + 합계 + 액션 --}}
        <div class="space-y-4">
            <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden sticky top-20">
                <div class="px-5 py-3.5 bg-neutral-50 border-b border-neutral-100 flex items-center justify-between">
                    <span class="font-extrabold text-neutral-800">거래 품목 <span class="text-mango-600" x-text="'(' + lines.length + ')'"></span></span>
                    <button type="button" @click="lines = []" x-show="lines.length" class="text-xs font-semibold text-rose-600 hover:underline">비우기</button>
                </div>

                <template x-if="lines.length === 0">
                    <p class="px-5 py-10 text-center text-sm text-neutral-400">왼쪽에서 품목을 추가하세요.</p>
                </template>

                <div class="divide-y divide-neutral-50" x-show="lines.length">
                    <template x-for="(l, i) in lines" :key="l.id">
                        <div class="px-5 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-bold text-neutral-800 text-sm truncate" x-text="l.name"></p>
                                <button type="button" @click="remove(i)" class="text-rose-400 hover:text-rose-600 text-xs shrink-0">✕</button>
                            </div>
                            <div class="flex items-center justify-between mt-1.5">
                                <div class="flex items-center gap-1">
                                    <button type="button" @click="l.qty = Math.max(1, l.qty - 1)" class="w-7 h-7 rounded bg-neutral-100 hover:bg-neutral-200 font-bold">−</button>
                                    <input type="number" min="1" max="99999" x-model.number="l.qty" class="w-14 text-center rounded border-neutral-200 text-sm py-1">
                                    <button type="button" @click="l.qty = l.qty + 1" class="w-7 h-7 rounded bg-neutral-100 hover:bg-neutral-200 font-bold">＋</button>
                                </div>
                                <span class="text-sm font-bold text-mango-700" x-text="(l.price * l.qty).toLocaleString() + '원'"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="px-5 py-4 border-t border-neutral-100">
                    <div class="flex justify-between items-end mb-1">
                        <span class="font-semibold text-neutral-600">공급가액(합계)</span>
                        <span class="text-2xl font-black text-mango-700"><span x-text="total().toLocaleString()"></span>원</span>
                    </div>
                    <p class="text-[11px] text-neutral-400 mb-4">* 부가세는 제품별 부가세구분(포함/별도/면세)에 따라 발행 시 계산됩니다.</p>
                    <button type="submit" name="send" value="1" :disabled="lines.length === 0"
                            class="w-full rounded-xl bg-gradient-to-r from-mango-500 to-mango-600 text-white font-black py-3 text-sm shadow disabled:opacity-40">📧 작성·저장 + 본사 전송</button>
                    <button type="submit" :disabled="lines.length === 0"
                            class="w-full mt-2 rounded-xl border border-neutral-200 hover:bg-neutral-50 text-neutral-700 font-bold py-2.5 text-sm disabled:opacity-40">저장만 (전송 안 함)</button>
                    <p class="text-[11px] text-neutral-400 mt-2 text-center">저장 후 이력에서 <b>세금계산서 발행</b>(본사 청구)·재전송을 할 수 있습니다.</p>
                </div>
            </div>
        </div>
    </div>
</form>
@endif
</div>

@push('scripts')
<script>
    function stmtBuilder(catalog) {
        return {
            catalog, search: '', lines: [],
            filtered() {
                const q = this.search.trim().toLowerCase();
                if (!q) return this.catalog;
                return this.catalog.filter(p => (p.name || '').toLowerCase().includes(q) || (p.code || '').toLowerCase().includes(q));
            },
            inCart(id) { return this.lines.some(l => l.id === id); },
            add(p) {
                const ex = this.lines.find(l => l.id === p.id);
                if (ex) { ex.qty++; return; }
                this.lines.push({ id: p.id, code: p.code, name: p.name, unit: p.unit, price: p.price, qty: 1 });
            },
            remove(i) { this.lines.splice(i, 1); },
            total() { return this.lines.reduce((s, l) => s + l.price * (parseInt(l.qty) || 0), 0); },
        };
    }
</script>
@endpush
@endsection
