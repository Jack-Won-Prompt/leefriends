@extends('portal.layout')
@section('title', '물품 관리')

@php $cats = $formCategories ?? \App\Http\Controllers\Portal\Supplier\ProductController::CATEGORIES; @endphp

@section('content')
@php
    $hasErr = $errors->any();
    $initForm = $hasErr
        ? array_merge(old())
        : ['category' => $cats[0], 'unit' => 'BOX', 'sort_order' => 0];
@endphp
<div x-data="Object.assign(crudModal({{ $hasErr ? 'true' : 'false' }}, {{ \Illuminate\Support\Js::from($initForm) }}), {
        mode: '{{ $hasErr ? old('_mode', 'create') : 'create' }}',
        action: '{{ $hasErr ? old('_action') : '' }}',
        method: '{{ $hasErr && old('_mode') === 'edit' ? 'PUT' : 'POST' }}',
     })">

<x-wms.page-head title="물품 관리" subtitle="공급할 물품을 등록합니다. 본사 승인 후 매장에서 발주할 수 있습니다." icon="📦">
    <x-slot:actions>
        <button type="button" @click="openCreate('{{ route('portal.supplier.products.store') }}', { category: '{{ $cats[0] }}', unit: 'BOX', spec: '', supply_price: 0, sort_order: 0 })"
                class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">+ 새 물품 등록</button>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.filter :action="route('portal.supplier.products.index')">
    <x-wms.field label="검색어 (물품명/코드)">
        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="물품명 또는 코드" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
    </x-wms.field>
    <x-wms.field label="승인상태">
        <select name="approval" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="all">전체</option>
            <option value="approved" @selected($filters['approval'] === 'approved')>승인</option>
            <option value="pending" @selected($filters['approval'] === 'pending')>승인대기</option>
            <option value="rejected" @selected($filters['approval'] === 'rejected')>반려</option>
        </select>
    </x-wms.field>
</x-wms.filter>

<x-wms.toolbar :count="$products->total()" />

<x-wms.panel>
    @if ($products->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">등록된 물품이 없습니다. «새 물품 등록»으로 추가해 주세요.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm whitespace-nowrap">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-5 py-3">물품코드</th>
                        <th class="text-left font-semibold px-5 py-3">물품명</th>
                        <th class="text-left font-semibold px-5 py-3">대분류</th>
                        <th class="text-left font-semibold px-5 py-3 hidden md:table-cell">규격</th>
                        <th class="text-left font-semibold px-5 py-3 hidden md:table-cell">단위</th>
                        <th class="text-right font-semibold px-5 py-3">공급가</th>
                        <th class="text-center font-semibold px-5 py-3">승인상태</th>
                        <th class="text-right font-semibold px-5 py-3 w-28">관리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($products as $p)
                        <tr class="hover:bg-mango-50/40 transition">
                            <td class="px-5 py-3.5 font-mono font-bold text-neutral-700">{{ $p->code }}</td>
                            <td class="px-5 py-3.5 font-bold text-neutral-900">{{ $p->name }}</td>
                            <td class="px-5 py-3.5 text-neutral-600">{{ $p->category }}</td>
                            <td class="px-5 py-3.5 hidden md:table-cell text-neutral-500">{{ $p->spec ?: '-' }}</td>
                            <td class="px-5 py-3.5 hidden md:table-cell text-neutral-500">{{ $p->unit }}</td>
                            <td class="px-5 py-3.5 text-right font-semibold text-sky-700">{{ number_format($p->supply_price) }}원</td>
                            <td class="px-5 py-3.5 text-center">
                                @php $ap = $p->approval_status; @endphp
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full
                                    {{ $ap === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($ap === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-600') }}">
                                    {{ $p->approval_label }}
                                </span>
                                @if ($ap === 'rejected' && $p->approval_note)
                                    <p class="text-[11px] text-rose-400 mt-1">{{ $p->approval_note }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex justify-end gap-2">
                                    @if ($p->approval_status === 'approved')
                                        <span class="text-xs text-neutral-400 px-2 py-1.5">승인 완료</span>
                                    @else
                                        <button type="button"
                                                @click="openEdit('{{ route('portal.supplier.products.update', $p) }}', {{ \Illuminate\Support\Js::from([
                                                    'name' => $p->name, 'code' => $p->code, 'category' => $p->category, 'spec' => $p->spec,
                                                    'unit' => $p->unit, 'supply_price' => $p->supply_price, 'sort_order' => $p->sort_order,
                                                ]) }})"
                                                class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1.5 font-semibold">수정</button>
                                        <form method="POST" action="{{ route('portal.supplier.products.destroy', $p) }}" onsubmit="return confirm('이 물품을 삭제하시겠습니까?')">
                                            @csrf @method('DELETE')
                                            <button class="rounded-lg text-rose-600 hover:bg-rose-50 px-3 py-1.5 font-semibold">삭제</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-wms.panel>

<div class="mt-5">{{ $products->links() }}</div>

{{-- 물품 등록/수정 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/50" @click="open=false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100 sticky top-0 bg-white rounded-t-2xl">
            <h2 class="text-lg font-extrabold text-neutral-900" x-text="mode === 'edit' ? '물품 수정' : '새 물품 등록'"></h2>
            <button type="button" @click="open=false" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-neutral-100 text-neutral-500">✕</button>
        </div>
        <form :action="action" method="POST" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="_method" :value="method">
            <input type="hidden" name="_mode" :value="mode">
            <input type="hidden" name="_action" :value="action">

            <div class="rounded-xl bg-amber-50 border border-amber-100 px-4 py-2.5 text-[12px] text-amber-700">
                ※ 등록한 물품은 <b>승인대기</b> 상태가 되며, 본사가 매장 판매가를 책정해 승인하면 매장에서 발주할 수 있습니다.
            </div>

            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">물품명</label>
                    <input type="text" name="name" x-model="form.name" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">물품코드 <span class="text-neutral-400 font-normal">(자동 채번)</span></label>
                    <input type="text" :value="form.code || '등록 시 자동 생성'" readonly class="w-full rounded-xl border-neutral-200 bg-neutral-100 text-neutral-500 font-mono cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">대분류</label>
                    <select name="category" x-model="form.category" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                        @foreach ($cats as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">규격 <span class="text-neutral-400 font-normal">(선택)</span></label>
                    <input type="text" name="spec" x-model="form.spec" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="10개입 / 1.68L / 3kg">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">단위</label>
                    <input type="text" name="unit" x-model="form.unit" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="BOX / EA">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">공급가 (원) <span class="text-neutral-400 font-normal">(공급처 → 본사)</span></label>
                    <input type="number" name="supply_price" x-model="form.supply_price" required min="0" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">정렬 순서</label>
                    <input type="number" name="sort_order" x-model="form.sort_order" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-7 py-3 transition" x-text="mode === 'edit' ? '수정 저장' : '등록'"></button>
                <button type="button" @click="open=false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 font-bold px-7 py-3 transition">취소</button>
            </div>
        </form>
    </div>
</div>

</div>

@include('portal.partials.crud-modal-script')
@endsection
