@extends('portal.layout')
@php $isSample = ($orderType ?? ($editOrder->order_type ?? 'normal')) === 'sample'; @endphp
@section('title', $editOrder ? ($isSample ? '샘플 주문 수정' : '발주 수정') : ($isSample ? '샘플 주문하기' : '재료 발주하기'))

@section('content')
@php
    // JS 카탈로그 (대분류별 탭 + 적용 리스트 구성용)
    $catalog = [];
    foreach ($products as $cat => $items) {
        foreach ($items as $p) {
            $catalog[$p->id] = [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'spec' => $p->spec,
                'category' => $cat,
                'units' => $p->units->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'price' => (int) $u->store_price])->values(),
            ];
        }
    }
    $categories = array_keys($products->toArray());
    // 수정 시 기존 발주 → 장바구니 초기값
    $initialCart = collect($prefill)->mapWithKeys(fn ($v, $k) => [$k => ['qty' => $v['qty'], 'unitId' => $v['unit_id']]])->all();
@endphp

@if ($isSample)
    <div class="mb-5 rounded-xl bg-violet-50 border border-violet-200 px-5 py-3 text-sm text-violet-800">
        🧪 <b>샘플 주문</b> — 신상품/시식용 샘플을 무상으로 주문합니다. <b>가격은 표시되지 않으며</b>, 본사와 해당 공급처가 주문을 확인합니다.
    </div>
@endif
@if ($editOrder)
    <div class="mb-5 rounded-xl bg-sky-50 border border-sky-200 px-5 py-3 text-sm text-sky-800">
        ✏️ <b>{{ $editOrder->order_no }}</b> 수정 중 — 저장 시 본사·공급처에 변경 알림이 전송되고, 해당 판매자는 확인(반영) 후 진행하게 됩니다.
    </div>
@endif

<form method="POST" action="{{ $editOrder ? route('portal.store.orders.update', $editOrder) : route('portal.store.orders.store') }}"
      x-data="orderForm({{ \Illuminate\Support\Js::from($catalog) }}, {{ \Illuminate\Support\Js::from($initialCart) }}, {{ $editOrder ? 'true' : 'false' }}, {{ \Illuminate\Support\Js::from($pastOrders) }})" x-init="init()">
    @csrf
    @if ($editOrder) @method('PUT') @endif
    <input type="hidden" name="order_type" value="{{ $isSample ? 'sample' : 'normal' }}">

    {{-- 지난 발주 이력에서 빠른 재발주 --}}
    <div class="mb-6 rounded-2xl bg-white shadow-sm border border-neutral-100 p-5" x-show="pastOrders.length">
        <div class="flex items-center gap-3 flex-wrap">
            <span class="font-extrabold text-neutral-800 shrink-0">🔁 지난 발주 이력</span>
            <select x-model="selectedPast"
                    class="flex-1 min-w-[240px] rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm py-2">
                <option value="">발주 이력을 선택하세요…</option>
                <template x-for="o in pastOrders" :key="o.id">
                    <option :value="o.id" x-text="o.order_no + ' · ' + o.date + ' · ' + o.count + '품목 · ' + o.amount.toLocaleString() + '원'"></option>
                </template>
            </select>
            <button type="button" @click="applyPastOrder()" :disabled="!selectedPast"
                    class="shrink-0 rounded-xl bg-mango-500 hover:bg-mango-600 disabled:opacity-40 text-white font-bold px-5 py-2 text-sm transition">
                이 발주 적용
            </button>
        </div>

        {{-- 선택한 발주 미리보기 --}}
        <template x-if="selectedOrder()">
            <div class="mt-3 flex flex-wrap gap-1.5">
                <template x-for="it in selectedOrder().items" :key="it.pid">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full"
                          :class="catalog[it.pid] ? 'bg-neutral-100 text-neutral-600' : 'bg-rose-50 text-rose-400 line-through'"
                          x-text="(catalog[it.pid] ? catalog[it.pid].name : '삭제된 품목') + ' ×' + it.qty"></span>
                </template>
            </div>
        </template>

        <p x-show="pastMsg" x-text="pastMsg" class="mt-2 text-sm font-semibold text-emerald-600"></p>
    </div>

    {{-- 적용된 품목만 제출되는 hidden 입력 --}}
    <template x-for="pid in Object.keys(cart)" :key="'h' + pid">
        <span>
            <input type="hidden" :name="`qty[${pid}]`" :value="cart[pid].qty">
            <input type="hidden" :name="`unit[${pid}]`" :value="cart[pid].unitId">
        </span>
    </template>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- 품목 선택 (대분류 탭) --}}
        <div class="lg:col-span-2">
            <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
                {{-- 검색 필터 --}}
                <div class="p-3 border-b border-neutral-100">
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none">🔍</span>
                        <input type="text" x-model="search" placeholder="품목명 · 코드 · 규격으로 검색"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm pl-9 pr-9 py-2.5">
                        <button type="button" x-show="search" @click="search = ''" x-cloak
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600" title="검색 지우기">✕</button>
                    </div>
                    <p x-show="search.trim()" x-cloak class="mt-2 text-xs text-neutral-500">
                        검색 결과 <b class="text-mango-600" x-text="searchCount()"></b>건
                    </p>
                </div>

                {{-- 탭 바 (검색 중에는 숨김) --}}
                <div x-show="!search.trim()" class="flex gap-1 px-3 pt-3 border-b border-neutral-100 overflow-x-auto">
                    @foreach ($categories as $cat)
                        <button type="button" @click="activeTab = '{{ $cat }}'"
                                class="px-4 py-2.5 text-sm font-bold rounded-t-lg whitespace-nowrap transition"
                                :class="activeTab === '{{ $cat }}' ? 'bg-mango-500 text-white' : 'text-neutral-500 hover:bg-neutral-100'">
                            {{ $cat }}
                            <span class="ml-1 text-xs opacity-80" x-text="'(' + countInCategory('{{ $cat }}') + ')'"></span>
                        </button>
                    @endforeach
                </div>

                {{-- 탭별 품목 --}}
                @foreach ($products as $category => $items)
                    <div x-show="search.trim() ? true : activeTab === '{{ $category }}'" class="divide-y divide-neutral-100">
                        @foreach ($items as $p)
                            @php $defaultUnit = $p->units->firstWhere('is_default', true) ?? $p->units->first(); @endphp
                            <div class="flex items-center gap-3 px-5 py-4"
                                 x-show="!search.trim() || matchesSearch({{ $p->id }})" x-cloak
                                 :class="cart[{{ $p->id }}] ? 'bg-mango-50/50' : ''">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-neutral-900">{{ $p->name }}</span>
                                        @if ($p->spec)
                                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600">{{ $p->spec }}</span>
                                        @endif
                                        @unless ($isSample)
                                            <span class="text-sm text-neutral-500">단가 <span class="font-bold text-mango-700" x-text="priceOf({{ $p->id }}, unitId[{{ $p->id }}]).toLocaleString() + '원'"></span></span>
                                        @endunless
                                        <span class="text-[11px] text-neutral-400">{{ $p->code }}</span>
                                        <template x-if="cart[{{ $p->id }}]">
                                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">적용됨</span>
                                        </template>
                                    </div>
                                </div>

                                {{-- 단위 선택 --}}
                                <div class="shrink-0">
                                    @php $selUnit = $prefill[$p->id]['unit_id'] ?? optional($defaultUnit)->id; @endphp
                                    <select x-model.number="unitId[{{ $p->id }}]"
                                            class="rounded-lg border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm py-2">
                                        @foreach ($p->units as $u)
                                            <option value="{{ $u->id }}" @selected($u->id === $selUnit)>
                                                {{ $u->name }}@unless ($isSample) ({{ number_format($u->store_price) }}원)@endunless
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- 수량 --}}
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button" @click="dec({{ $p->id }})" class="w-9 h-9 rounded-lg bg-neutral-100 hover:bg-neutral-200 font-bold text-lg">−</button>
                                    <input type="number" min="1" max="9999" x-model.number="qty[{{ $p->id }}]"
                                           class="w-16 text-center rounded-lg border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                                    <button type="button" @click="inc({{ $p->id }})" class="w-9 h-9 rounded-lg bg-neutral-100 hover:bg-neutral-200 font-bold text-lg">＋</button>
                                </div>

                                {{-- 적용 --}}
                                <button type="button" @click="apply({{ $p->id }})"
                                        class="shrink-0 rounded-lg px-4 py-2 text-sm font-bold transition"
                                        :class="cart[{{ $p->id }}] ? 'bg-emerald-500 hover:bg-emerald-600 text-white' : 'bg-mango-500 hover:bg-mango-600 text-white'"
                                        x-text="cart[{{ $p->id }}] ? '수정 적용' : '적용'"></button>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                {{-- 검색 결과 없음 --}}
                <div x-show="searchCount() === 0" x-cloak class="px-5 py-12 text-center text-neutral-400 text-sm">
                    «<span x-text="search"></span>» 검색 결과가 없습니다.
                </div>
            </div>
        </div>

        {{-- 발주 요약 --}}
        <div class="space-y-4">
            <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-6 sticky top-20">
                <h3 class="font-extrabold text-neutral-900 mb-4">{{ $isSample ? '샘플 주문 요약' : '발주 요약' }}</h3>
                <div class="flex justify-between text-sm py-1.5">
                    <span class="text-neutral-500">선택 품목</span>
                    <span class="font-bold" x-text="itemCount + '개'"></span>
                </div>
                <div class="flex justify-between text-sm py-1.5">
                    <span class="text-neutral-500">총 수량</span>
                    <span class="font-bold" x-text="totalQty + '개'"></span>
                </div>
                <div class="border-t border-neutral-100 my-3"></div>
                @if ($isSample)
                    <div class="flex justify-between items-end">
                        <span class="text-neutral-600 font-semibold">결제 금액</span>
                        <span class="text-lg font-black text-violet-600">무상 (샘플)</span>
                    </div>
                @else
                    <div class="flex justify-between items-end">
                        <span class="text-neutral-600 font-semibold">결제 예상금액</span>
                        <span class="text-2xl font-black text-mango-700"><span x-text="totalAmount.toLocaleString()"></span>원</span>
                    </div>
                @endif

                <textarea name="note" rows="2" placeholder="요청사항 (선택)"
                          class="w-full mt-4 rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">{{ old('note') }}</textarea>

                <button type="button" @click="askConfirm()"
                        class="w-full mt-4 rounded-xl text-white font-black py-3.5 shadow hover:brightness-105 active:scale-[0.99] transition disabled:opacity-50 {{ $isSample ? 'bg-gradient-to-r from-violet-500 to-violet-600' : 'bg-gradient-to-r from-mango-500 to-mango-600' }}"
                        x-bind:disabled="itemCount === 0 || submitting">
                    <span x-show="!submitting">{{ $editOrder ? ($isSample ? '샘플 주문 수정 저장' : '발주 수정 저장') : ($isSample ? '샘플 주문 접수하기' : '발주 접수하기') }}</span>
                    <span x-show="submitting" x-cloak>처리 중…</span>
                </button>
                <p class="text-[11px] text-neutral-400 mt-3 leading-relaxed">
                    * 수량 입력 후 <b>적용</b>을 눌러 {{ $isSample ? '샘플 주문 리스트' : '구매 리스트' }}에 담아 주세요.
                </p>
            </div>
        </div>
    </div>

    {{-- 적용한 재료 구매 리스트 --}}
    <div class="mt-6 rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
        <div class="px-6 py-3.5 bg-neutral-50 border-b border-neutral-100 flex items-center justify-between">
            <span class="font-extrabold text-neutral-800">{{ $isSample ? '샘플 주문 리스트' : '구매 리스트' }} <span class="text-mango-600" x-text="'(' + itemCount + ')'"></span></span>
            <button type="button" @click="clearCart()" x-show="itemCount > 0" class="text-xs font-semibold text-rose-600 hover:underline">전체 비우기</button>
        </div>

        <template x-if="itemCount === 0">
            <p class="px-6 py-12 text-center text-neutral-400 text-sm">아직 적용한 품목이 없습니다. 위에서 수량 입력 후 <b>적용</b> 버튼을 눌러 주세요.</p>
        </template>

        <template x-if="itemCount > 0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm whitespace-nowrap">
                    <thead class="bg-white text-neutral-500 border-b border-neutral-100">
                        <tr>
                            <th class="text-left font-semibold px-5 py-3">품목코드</th>
                            <th class="text-left font-semibold px-5 py-3">대분류</th>
                            <th class="text-left font-semibold px-5 py-3">품목명</th>
                            <th class="text-left font-semibold px-5 py-3 hidden md:table-cell">규격</th>
                            <th class="text-left font-semibold px-5 py-3">단위</th>
                            @unless ($isSample)<th class="text-right font-semibold px-5 py-3">단가</th>@endunless
                            <th class="text-center font-semibold px-5 py-3">수량</th>
                            @unless ($isSample)<th class="text-right font-semibold px-5 py-3">금액</th>@endunless
                            <th class="text-right font-semibold px-5 py-3 w-20">관리</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        <template x-for="pid in Object.keys(cart)" :key="pid">
                            <tr class="hover:bg-mango-50/40">
                                <td class="px-5 py-3 font-mono font-bold text-neutral-700" x-text="catalog[pid].code"></td>
                                <td class="px-5 py-3 text-neutral-600" x-text="catalog[pid].category"></td>
                                <td class="px-5 py-3 font-bold text-neutral-900" x-text="catalog[pid].name"></td>
                                <td class="px-5 py-3 hidden md:table-cell text-neutral-500" x-text="catalog[pid].spec || '-'"></td>
                                <td class="px-5 py-3 text-neutral-500" x-text="unitName(pid, cart[pid].unitId)"></td>
                                @unless ($isSample)<td class="px-5 py-3 text-right text-neutral-600" x-text="priceOf(pid, cart[pid].unitId).toLocaleString() + '원'"></td>@endunless
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" @click="cart[pid].qty = Math.max(1, cart[pid].qty - 1); recalc()" class="w-7 h-7 rounded bg-neutral-100 hover:bg-neutral-200 font-bold">−</button>
                                        <input type="number" min="1" max="9999" x-model.number="cart[pid].qty" @input="recalc()" class="w-14 text-center rounded border-neutral-200 text-sm py-1">
                                        <button type="button" @click="cart[pid].qty = cart[pid].qty + 1; recalc()" class="w-7 h-7 rounded bg-neutral-100 hover:bg-neutral-200 font-bold">＋</button>
                                    </div>
                                </td>
                                @unless ($isSample)<td class="px-5 py-3 text-right font-bold text-mango-700" x-text="(priceOf(pid, cart[pid].unitId) * cart[pid].qty).toLocaleString() + '원'"></td>@endunless
                                <td class="px-5 py-3 text-right">
                                    <button type="button" @click="removeFromCart(pid)" class="rounded-lg text-rose-600 hover:bg-rose-50 px-3 py-1.5 font-semibold">삭제</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    @unless ($isSample)
                    <tfoot class="bg-neutral-50 border-t border-neutral-100">
                        <tr>
                            <td colspan="6" class="px-5 py-3 text-right font-bold text-neutral-600">합계</td>
                            <td class="px-5 py-3 text-right font-black text-mango-700"><span x-text="totalAmount.toLocaleString()"></span>원</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endunless
                </table>
            </div>
        </template>
    </div>

    {{-- 발주 확인 팝업 (커스텀 alert) --}}
    <div x-show="confirmOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @keydown.escape.window="!submitting && (confirmOpen = false)">
        <div class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6"
             @click.outside="!submitting && (confirmOpen = false)">
            <div class="text-center">
                <div class="text-4xl mb-3">{{ $isSample ? '🎁' : '🧾' }}</div>
                <h3 class="text-lg font-extrabold text-neutral-900">{{ $isSample ? '샘플 주문하시겠습니까?' : '발주하시겠습니까?' }}</h3>
                <p class="text-sm text-neutral-500 mt-1.5">
                    선택 품목 <b class="text-neutral-700" x-text="itemCount"></b>건 · 총 <b class="text-neutral-700" x-text="totalQty"></b>개
                    @unless ($isSample)
                        <br>결제 예상금액 <b class="text-mango-700"><span x-text="totalAmount.toLocaleString()"></span>원</b>
                    @endunless
                </p>
            </div>
            <div class="flex gap-2 mt-6">
                <button type="button" @click="confirmOpen = false" :disabled="submitting"
                        class="flex-1 rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold py-3 disabled:opacity-50">취소</button>
                <button type="button" @click="submitOrder()" :disabled="submitting"
                        class="flex-1 rounded-xl text-white font-bold py-3 disabled:opacity-60 {{ $isSample ? 'bg-violet-500 hover:bg-violet-600' : 'bg-mango-500 hover:bg-mango-600' }}">
                    <span x-show="!submitting">{{ $isSample ? '주문하기' : '발주하기' }}</span>
                    <span x-show="submitting" x-cloak>처리 중…</span>
                </button>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
    function orderForm(catalog = {}, initialCart = {}, editMode = false, pastOrders = []) {
        return {
            catalog: catalog,
            editMode: editMode,
            pastOrders: pastOrders,
            selectedPast: '',
            search: '',
            pastMsg: '',
            activeTab: Object.keys(catalog).length ? catalog[Object.keys(catalog)[0]].category : '',
            qty: {},        // 작업 중 수량 (적용 전)
            unitId: {},     // 작업 중 단위 (적용 전)
            cart: {},       // 적용된 구매 리스트: pid -> { qty, unitId }
            itemCount: 0, totalQty: 0, totalAmount: 0,
            confirmOpen: false, // 발주 확인 팝업
            submitting: false,  // 전송 중(중복 클릭 방지)
            askConfirm() {
                if (this.itemCount === 0 || this.submitting) return;
                this.confirmOpen = true;
            },
            submitOrder() {
                if (this.submitting) return;   // 연속 클릭 방지
                this.submitting = true;
                // $el(컴포넌트 루트=form) 또는 가장 가까운 form을 찾아 네이티브 submit 직접 호출
                const form = (this.$el && this.$el.closest) ? this.$el.closest('form') : null;
                const target = form || this.$el;
                HTMLFormElement.prototype.submit.call(target);
            },
            matchesSearch(pid) {
                const q = (this.search || '').trim().toLowerCase();
                if (!q) return true;
                const p = this.catalog[pid];
                if (!p) return false;
                return (p.name || '').toLowerCase().includes(q)
                    || (p.code || '').toLowerCase().includes(q)
                    || (p.spec || '').toLowerCase().includes(q);
            },
            searchCount() {
                if (!(this.search || '').trim()) return -1;
                return Object.keys(this.catalog).filter(pid => this.matchesSearch(pid)).length;
            },
            init() {
                // 각 품목의 기본 수량 1 / 기본 단위
                Object.values(this.catalog).forEach(p => {
                    this.qty[p.id] = 1;
                    this.unitId[p.id] = p.units.length ? p.units[0].id : null;
                });
                // 수정 모드: 기존 발주를 구매 리스트로 복원
                Object.keys(initialCart).forEach(pid => {
                    if (!this.catalog[pid]) return;
                    this.cart[pid] = { qty: initialCart[pid].qty, unitId: initialCart[pid].unitId };
                    this.qty[pid] = initialCart[pid].qty;
                    this.unitId[pid] = initialCart[pid].unitId;
                });
                this.recalc();
            },
            unitObj(pid, unitId) {
                const p = this.catalog[pid];
                if (!p) return null;
                return p.units.find(u => u.id == unitId) || p.units[0] || null;
            },
            priceOf(pid, unitId) { const u = this.unitObj(pid, unitId); return u ? u.price : 0; },
            unitName(pid, unitId) { const u = this.unitObj(pid, unitId); return u ? u.name : ''; },
            inc(pid) { this.qty[pid] = (parseInt(this.qty[pid]) || 0) + 1; },
            dec(pid) { this.qty[pid] = Math.max(1, (parseInt(this.qty[pid]) || 1) - 1); },
            countInCategory(cat) {
                return Object.keys(this.cart).filter(pid => this.catalog[pid] && this.catalog[pid].category === cat).length;
            },
            apply(pid) {
                const q = parseInt(this.qty[pid]) || 0;
                if (q <= 0) { this.removeFromCart(pid); return; }
                this.cart[pid] = { qty: q, unitId: this.unitId[pid] };
                this.recalc();
            },
            removeFromCart(pid) {
                const c = Object.assign({}, this.cart);
                delete c[pid];
                this.cart = c;
                this.recalc();
            },
            clearCart() { this.cart = {}; this.recalc(); },
            selectedOrder() {
                return this.pastOrders.find(o => o.id == this.selectedPast) || null;
            },
            applyPastOrder() {
                const o = this.selectedOrder();
                if (!o) return;
                let applied = 0, skipped = 0;
                o.items.forEach(it => {
                    const p = this.catalog[it.pid];
                    if (!p) { skipped++; return; }
                    const u = p.units.find(u => u.id == it.unitId) || p.units[0];
                    const uid = u ? u.id : it.unitId;
                    this.cart[it.pid] = { qty: it.qty, unitId: uid };
                    this.qty[it.pid] = it.qty;     // 작업 상태도 동기화
                    this.unitId[it.pid] = uid;
                    applied++;
                });
                this.recalc();
                this.pastMsg = `${o.order_no}의 ${applied}개 품목을 구매 리스트에 담았습니다.`
                    + (skipped ? ` (${skipped}개는 현재 판매 중지되어 제외)` : '');
            },
            recalc() {
                let count = 0, totalQty = 0, amount = 0;
                for (const pid of Object.keys(this.cart)) {
                    const it = this.cart[pid];
                    const q = parseInt(it.qty) || 0;
                    count++; totalQty += q; amount += q * this.priceOf(pid, it.unitId);
                }
                this.itemCount = count; this.totalQty = totalQty; this.totalAmount = amount;
            },
        }
    }
</script>
@endpush
@endsection
