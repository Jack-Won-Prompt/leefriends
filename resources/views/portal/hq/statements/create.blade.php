@extends('portal.layout')
@section('title', '거래명세서')

@section('content')
<div x-data="statementBuilder({{ \Illuminate\Support\Js::from($catalog) }}, {{ \Illuminate\Support\Js::from($stores) }})">
<x-wms.page-head title="거래명세서 작성" subtitle="매장과 품목을 선택하면 금액이 자동 계산됩니다. 작성 후 매장 이메일로 PDF를 전송하세요." icon="🧾" />

<form method="POST" action="{{ route('portal.hq.statements.send') }}"
      onsubmit="return confirmSend(event)">
    @csrf
    <input type="hidden" name="store_id" :value="storeId">
    <template x-for="(l, i) in lines" :key="l.id">
        <span>
            <input type="hidden" :name="`items[${i}][product_id]`" :value="l.id">
            <input type="hidden" :name="`items[${i}][qty]`" :value="l.qty">
        </span>
    </template>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- 좌: 매장 + 품목 선택 --}}
        <div class="lg:col-span-2 space-y-5">
            {{-- 매장 선택 --}}
            <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-5">
                <label class="block text-sm font-extrabold text-neutral-900 mb-2">받는 매장 (공급받는자)</label>
                <select x-model="storeId" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    <option value="">매장을 선택하세요…</option>
                    <template x-for="s in stores" :key="s.id">
                        <option :value="s.id" x-text="s.name + (s.email ? '' : ' (이메일 없음)')"></option>
                    </template>
                </select>
                <template x-if="selectedStore()">
                    <p class="mt-2 text-xs" :class="selectedStore().email ? 'text-neutral-500' : 'text-rose-500 font-semibold'">
                        <span x-text="selectedStore().email ? ('📧 ' + selectedStore().email) : '⚠ 이 매장은 이메일이 없습니다. 매장 관리에서 이메일을 등록해야 전송할 수 있습니다.'"></span>
                    </p>
                </template>
            </div>

            {{-- 품목 카탈로그 --}}
            <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
                <div class="p-3 border-b border-neutral-100">
                    <input type="text" x-model="search" placeholder="🔍 품목명 · 코드 검색"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
                <div class="max-h-[420px] overflow-y-auto divide-y divide-neutral-50">
                    <template x-for="p in filtered()" :key="p.id">
                        <div class="flex items-center gap-3 px-5 py-3">
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-neutral-900 text-sm" x-text="p.name"></p>
                                <p class="text-xs text-neutral-400"><span x-text="p.code"></span> · <span x-text="p.category"></span> · 단가 <span class="font-bold text-mango-700" x-text="p.price.toLocaleString() + '원'"></span></p>
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
                <div class="px-5 py-3.5 border-b border-neutral-100">
                    <label class="block text-xs font-semibold text-neutral-500 mb-1">거래명세서 발행일자</label>
                    <input type="date" name="statement_date" value="{{ now()->format('Y-m-d') }}"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm py-2">
                </div>
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
                    <div class="flex justify-between items-end mb-4">
                        <span class="font-semibold text-neutral-600">합계</span>
                        <span class="text-2xl font-black text-mango-700"><span x-text="total().toLocaleString()"></span>원</span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="submit" formaction="{{ route('portal.hq.statements.preview') }}" formtarget="_blank"
                                :disabled="!canSubmit()"
                                class="rounded-xl border border-neutral-200 hover:bg-neutral-50 text-neutral-700 font-bold py-2.5 text-sm disabled:opacity-40">미리보기(PDF)</button>
                        <button type="submit"
                                :disabled="!canSubmit()"
                                class="rounded-xl bg-gradient-to-r from-mango-500 to-mango-600 text-white font-bold py-2.5 text-sm disabled:opacity-40">📧 이메일 전송</button>
                    </div>
                    <p class="text-[11px] text-neutral-400 mt-2 text-center">전송 시 선택한 매장 이메일로 거래명세서 PDF가 발송됩니다.</p>
                </div>
            </div>
        </div>
    </div>
</form>
</div>

@push('scripts')
<script>
    function statementBuilder(catalog, stores) {
        return {
            catalog, stores, storeId: '', search: '', lines: [],
            filtered() {
                const q = this.search.trim().toLowerCase();
                if (!q) return this.catalog;
                return this.catalog.filter(p => (p.name || '').toLowerCase().includes(q) || (p.code || '').toLowerCase().includes(q));
            },
            selectedStore() { return this.stores.find(s => String(s.id) === String(this.storeId)) || null; },
            inCart(id) { return this.lines.some(l => l.id === id); },
            add(p) {
                const ex = this.lines.find(l => l.id === p.id);
                if (ex) { ex.qty++; return; }
                this.lines.push({ id: p.id, code: p.code, name: p.name, unit: p.unit, price: p.price, qty: 1 });
            },
            remove(i) { this.lines.splice(i, 1); },
            total() { return this.lines.reduce((s, l) => s + l.price * (parseInt(l.qty) || 0), 0); },
            canSubmit() { return this.storeId && this.lines.length > 0; },
        };
    }
    function confirmSend(e) {
        // formaction 이 preview 인 버튼(미리보기)은 confirm 생략
        if (e.submitter && e.submitter.formAction && e.submitter.formAction.includes('/preview')) return true;
        return confirm('선택한 매장 이메일로 거래명세서 PDF를 전송할까요?');
    }
</script>
@endpush
@endsection
