@extends('portal.layout')
@section('title', '구매발주 등록')

@section('content')
<div x-data="poCreate()" x-init="init()">
<x-wms.page-head title="구매발주 등록" subtitle="공급처를 선택하고 매입할 품목을 담아 발주합니다." icon="🧾" />

@if ($errors->any())
    <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-5 py-3 text-sm">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('portal.hq.purchase_orders.store') }}">
    @csrf
    <input type="hidden" name="supplier_id" :value="supplierId">
    <template x-for="(row, i) in cart" :key="row.pid">
        <span>
            <input type="hidden" :name="`items[${i}][product_id]`" :value="row.pid">
            <input type="hidden" :name="`items[${i}][qty]`" :value="row.qty">
        </span>
    </template>

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- 품목 선택 --}}
        <x-wms.panel class="p-6">
            <label class="block text-sm font-bold text-neutral-700 mb-1.5">공급처</label>
            <select x-model="supplierId" @change="cart=[]" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 mb-4">
                <option value="">공급처를 선택하세요</option>
                @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
            </select>

            <template x-if="supplierId">
                <div>
                    <input type="text" x-model="q" placeholder="품목 검색" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 mb-3 text-sm">
                    <div class="max-h-96 overflow-y-auto divide-y divide-neutral-100 border border-neutral-100 rounded-xl">
                        <template x-for="p in filtered()" :key="p.id">
                            <div class="flex items-center justify-between px-4 py-2.5 hover:bg-mango-50/40">
                                <div>
                                    <p class="font-semibold text-neutral-800 text-sm" x-text="p.name"></p>
                                    <p class="text-xs text-neutral-400"><span x-text="p.unit"></span> · <span x-text="Number(p.price).toLocaleString()"></span>원</p>
                                </div>
                                <button type="button" @click="add(p)" class="rounded-lg bg-neutral-100 hover:bg-mango-100 text-neutral-600 font-bold px-3 py-1.5 text-xs">담기</button>
                            </div>
                        </template>
                        <template x-if="filtered().length === 0"><p class="px-4 py-8 text-center text-neutral-400 text-sm">품목이 없습니다.</p></template>
                    </div>
                </div>
            </template>
        </x-wms.panel>

        {{-- 담은 품목 --}}
        <x-wms.panel class="p-6">
            <h3 class="font-extrabold text-neutral-900 mb-4">발주 품목 <span class="text-mango-600" x-text="cart.length"></span></h3>
            <template x-if="cart.length === 0"><p class="text-neutral-400 text-sm py-8 text-center">왼쪽에서 품목을 담아주세요.</p></template>
            <div class="divide-y divide-neutral-100">
                <template x-for="(row, i) in cart" :key="row.pid">
                    <div class="flex items-center gap-3 py-2.5">
                        <div class="flex-1">
                            <p class="font-semibold text-neutral-800 text-sm" x-text="row.name"></p>
                            <p class="text-xs text-neutral-400"><span x-text="Number(row.price).toLocaleString()"></span>원 · <span x-text="row.unit"></span></p>
                        </div>
                        <input type="number" min="1" x-model.number="row.qty" class="w-20 rounded-lg border-neutral-200 text-sm py-1.5 text-right">
                        <span class="w-24 text-right font-bold text-neutral-700 text-sm" x-text="Number(row.price*row.qty).toLocaleString()+'원'"></span>
                        <button type="button" @click="cart.splice(i,1)" class="text-rose-400 hover:text-rose-600">✕</button>
                    </div>
                </template>
            </div>
            <div class="flex justify-between items-center border-t border-neutral-200 mt-4 pt-4">
                <span class="font-bold text-neutral-700">합계</span>
                <span class="text-xl font-black text-mango-700" x-text="Number(total()).toLocaleString()+'원'"></span>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">메모</label>
                <textarea name="note" rows="2" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="배송 요청사항 등"></textarea>
            </div>
            <button type="submit" :disabled="!supplierId || cart.length===0" class="w-full mt-4 rounded-xl bg-mango-500 hover:bg-mango-600 disabled:opacity-40 text-white font-black py-3.5 transition">구매발주 등록 · 공급처 전송</button>
        </x-wms.panel>
    </div>
</form>
</div>

@push('scripts')
<script>
    function poCreate() {
        return {
            supplierId: '', q: '', cart: [],
            products: @json($products),
            init() {},
            filtered() {
                return this.products.filter(p => String(p.supplier_id) === String(this.supplierId)
                    && (!this.q || p.name.includes(this.q))
                    && !this.cart.some(c => c.pid === p.id));
            },
            add(p) { this.cart.push({ pid: p.id, name: p.name, unit: p.unit, price: p.price, qty: 1 }); },
            total() { return this.cart.reduce((s, r) => s + (r.price * (r.qty || 0)), 0); },
        };
    }
</script>
@endpush
@endsection
