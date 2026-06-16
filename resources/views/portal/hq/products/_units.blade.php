{{-- 단위/매장판매가 + 공급처×단위 공급가. 상위 form x-data="productForm(...)" 안에서 사용 --}}
<div class="rounded-xl border border-neutral-200 p-4">
    <div class="flex items-center justify-between mb-2">
        <label class="text-sm font-bold text-neutral-700">단위 / 매장 판매가 <span class="text-neutral-400 font-normal">(주문 시 선택)</span></label>
        <button type="button" @click="addUnit()" class="text-sm font-bold text-mango-600 hover:text-mango-700">+ 단위 추가</button>
    </div>

    <div class="hidden sm:flex items-center gap-2 px-1 pb-1 text-[11px] font-bold text-neutral-400">
        <span class="w-6 text-center">기본</span>
        <span class="flex-1">단위명</span>
        <span class="w-32 text-right">매장 판매가</span>
        <span class="w-7"></span>
    </div>

    <div class="space-y-2">
        <template x-for="(u, i) in units" :key="i">
            <div class="flex items-center gap-2">
                <div class="w-6 text-center">
                    <input type="radio" name="default_unit" :value="i" x-model.number="def" class="text-mango-500 focus:ring-mango-400" title="기본 단위">
                </div>
                <input type="text" :name="'units['+i+'][name]'" x-model="u.name" required maxlength="30"
                       placeholder="개 / 박스 / kg"
                       class="flex-1 rounded-lg border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                <input type="number" :name="'units['+i+'][store_price]'" x-model.number="u.store_price" min="0" required
                       placeholder="매장 판매가"
                       class="w-32 rounded-lg border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm text-right">
                <button type="button" @click="removeUnit(i)" x-show="units.length > 1"
                        class="w-7 h-7 grid place-items-center rounded-lg text-rose-500 hover:bg-rose-50 shrink-0">✕</button>
            </div>
        </template>
    </div>
    <p class="text-[11px] text-neutral-400 mt-2">◉ 선택된 단위가 기본 단위입니다. 매장 판매가(출고가)는 모든 공급처 공통입니다.</p>
</div>

{{-- 공급처 × 단위 공급가 (공급처 직배송일 때만) --}}
<div x-show="type === 'supplier'" x-cloak class="rounded-xl border border-sky-200 bg-sky-50/40 p-4">
    <div class="flex items-center justify-between mb-2">
        <label class="text-sm font-bold text-neutral-700">공급처별 공급가 <span class="text-neutral-400 font-normal">(단위별 · 본사 청구 기준)</span></label>
        <button type="button" @click="addSupplier()" class="text-sm font-bold text-sky-600 hover:text-sky-700">+ 공급처 추가</button>
    </div>

    <div class="space-y-3">
        <template x-for="(s, si) in suppliers" :key="si">
            <div class="rounded-lg bg-white border border-neutral-200 p-3">
                <div class="flex items-center gap-2 mb-2">
                    <label class="flex items-center gap-1.5 text-xs font-bold text-neutral-500 shrink-0">
                        <input type="radio" name="default_supplier" :value="si" x-model.number="defSup" class="text-mango-500 focus:ring-mango-400"> 기본
                    </label>
                    <select :name="'suppliers['+si+'][supplier_id]'" x-model.number="s.supplier_id"
                            class="flex-1 rounded-lg border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                        <option value="">공급처 선택</option>
                        @foreach ($suppliers as $sp)
                            <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" @click="removeSupplier(si)" x-show="suppliers.length > 1"
                            class="w-7 h-7 grid place-items-center rounded-lg text-rose-500 hover:bg-rose-50 shrink-0">✕</button>
                </div>
                <div class="grid gap-1.5" :style="'grid-template-columns: repeat('+Math.min(units.length,3)+', minmax(0,1fr))'">
                    <template x-for="(u, ui) in units" :key="ui">
                        <div>
                            <label class="block text-[11px] text-neutral-400 mb-0.5" x-text="(u.name||'단위')+' 공급가'"></label>
                            <input type="number" :name="'suppliers['+si+'][prices]['+ui+']'" x-model.number="s.prices[ui]" min="0"
                                   class="w-full rounded-lg border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm text-right">
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>
    <p class="text-[11px] text-neutral-400 mt-2">◉ 기본 공급처로 발주가 라우팅되고 본사에 청구됩니다. 나머지는 단가 비교/대체용입니다.</p>
</div>

@once
@push('scripts')
<script>
    function productForm(units, type, def, suppliers, defSup, opts = {}) {
        const norm = (units) => (units && units.length) ? units.map(x => ({ name: x.name ?? '', store_price: x.store_price ?? 0 }))
                                                        : [{ name: '', store_price: 0 }];
        const normSup = (suppliers) => (suppliers && suppliers.length) ? suppliers.map(s => ({
            supplier_id: s.supplier_id ?? '', prices: (s.prices ?? []).slice()
        })) : [{ supplier_id: '', prices: [] }];
        const emptyP = { name: '', code: '', category: '원물', sort_order: 0, is_active: true };
        return {
            type, def: def ?? 0, defSup: defSup ?? 0,
            units: norm(units),
            suppliers: normSup(suppliers),
            p: Object.assign({}, emptyP, opts.p ?? {}),
            open: opts.open ?? false,
            action: opts.action ?? '',
            method: opts.method ?? 'POST',
            createUrl: opts.createUrl ?? '',
            init() { this.syncPrices(); },
            openCreate() {
                this.type = 'supplier'; this.def = 0; this.defSup = 0;
                this.units = [{ name: '', store_price: 0 }];
                this.suppliers = [{ supplier_id: '', prices: [0] }];
                this.p = Object.assign({}, emptyP);
                this.action = this.createUrl; this.method = 'POST'; this.open = true;
            },
            openEdit(data) {
                this.type = data.type; this.def = data.def ?? 0; this.defSup = data.defSup ?? 0;
                this.units = norm(data.units);
                this.suppliers = normSup(data.suppliers);
                this.p = Object.assign({}, emptyP, data.p ?? {});
                this.syncPrices();
                this.action = data.action; this.method = 'PUT'; this.open = true;
            },
            addUnit() { this.units.push({ name: '', store_price: 0 }); this.syncPrices(); },
            removeUnit(i) {
                if (this.units.length <= 1) return;
                this.units.splice(i, 1);
                this.suppliers.forEach(s => s.prices.splice(i, 1));
                if (this.def >= this.units.length) this.def = this.units.length - 1;
            },
            addSupplier() { this.suppliers.push({ supplier_id: '', prices: this.units.map(() => 0) }); },
            removeSupplier(i) {
                if (this.suppliers.length <= 1) return;
                this.suppliers.splice(i, 1);
                if (this.defSup >= this.suppliers.length) this.defSup = this.suppliers.length - 1;
            },
            syncPrices() {
                this.suppliers.forEach(s => {
                    while (s.prices.length < this.units.length) s.prices.push(0);
                    s.prices.length = this.units.length;
                });
            },
        }
    }
</script>
@endpush
@endonce
