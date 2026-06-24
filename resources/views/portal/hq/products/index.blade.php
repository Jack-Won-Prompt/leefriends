@extends('portal.layout')
@section('title', '품목 관리')

@php $finishedCategories = \App\Http\Controllers\Portal\Hq\ProductController::CATEGORIES; @endphp

@section('content')
@php
    $hasErr = $errors->any();
    $initForm = $hasErr
        ? array_merge(['is_active' => (bool) old('is_active')], old())
        : ['is_active' => true, 'category' => $finishedCategories[0], 'unit' => '개', 'sort_order' => 0];
@endphp
<div x-data="Object.assign(crudModal({{ $hasErr ? 'true' : 'false' }}, {{ \Illuminate\Support\Js::from($initForm) }}), {
        mode: '{{ $hasErr ? old('_mode', 'create') : 'create' }}',
        action: '{{ $hasErr ? old('_action') : '' }}',
        method: '{{ $hasErr && old('_mode') === 'edit' ? 'PUT' : 'POST' }}',
        approveOpen: false, approveAction: '', approveName: '', approveStore: 0, approveSupply: 0,
        openApprove(action, name, supply, store) {
            this.approveAction = action; this.approveName = name;
            this.approveSupply = supply; this.approveStore = store || supply;
            this.approveOpen = true;
        },
     })">

<x-wms.page-head title="품목 관리" subtitle="매장이 발주하는 품목(마카롱·쿠키·재료)과 단가를 관리합니다" icon="🍧">
    <x-slot:actions>
        <button type="button" @click="openCreate('{{ route('portal.hq.products.store') }}', { is_active: true, category: '{{ $finishedCategories[0] }}', unit: '개', spec: '', store_price: 0, tax_type: 'inc', supply_type: 'hq', supplier_id: '', supply_price: 0, sort_order: 0, image: null })"
                class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">+ 새 품목 추가</button>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.filter :action="route('portal.hq.products.index')">
    <x-wms.field label="검색어 (품목명/코드)">
        <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="품목명 또는 코드" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
    </x-wms.field>
    <x-wms.field label="분류">
        <select name="category" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="all">전체</option>
            @foreach ($categories as $cat)<option value="{{ $cat }}" @selected($filters['category'] === $cat)>{{ $cat }}</option>@endforeach
        </select>
    </x-wms.field>
    <x-wms.field label="노출">
        <select name="active" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="all">전체</option>
            <option value="active" @selected($filters['active'] === 'active')>노출</option>
            <option value="hidden" @selected($filters['active'] === 'hidden')>숨김</option>
        </select>
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
        <p class="px-6 py-16 text-center text-neutral-400">등록된 품목이 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm whitespace-nowrap">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-center font-semibold px-5 py-3 w-16">이미지</th>
                        <th class="text-left font-semibold px-5 py-3">품목코드</th>
                        <th class="text-left font-semibold px-5 py-3">품목명</th>
                        <th class="text-left font-semibold px-5 py-3">분류</th>
                        <th class="text-left font-semibold px-5 py-3 hidden md:table-cell">규격</th>
                        <th class="text-left font-semibold px-5 py-3 hidden md:table-cell">단위</th>
                        <th class="text-left font-semibold px-5 py-3">공급구분</th>
                        <th class="text-right font-semibold px-5 py-3">판매가</th>
                        <th class="text-right font-semibold px-5 py-3 hidden lg:table-cell">원가 · 마진</th>
                        <th class="text-center font-semibold px-5 py-3">승인상태</th>
                        <th class="text-center font-semibold px-5 py-3 hidden md:table-cell">노출</th>
                        <th class="text-right font-semibold px-5 py-3 w-28">관리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($products as $p)
                        <tr class="hover:bg-mango-50/40 transition {{ $p->is_active ? '' : 'opacity-50' }}">
                            <td class="px-5 py-3.5 text-center">
                                @if ($p->image)
                                    <img src="{{ asset($p->image) }}" alt="{{ $p->name }}" class="w-11 h-11 rounded-lg object-cover ring-1 ring-neutral-200 inline-block">
                                @else
                                    <span class="w-11 h-11 rounded-lg bg-neutral-100 text-neutral-300 grid place-items-center text-lg inline-grid">🍧</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 font-mono font-bold text-neutral-700">{{ $p->code }}</td>
                            <td class="px-5 py-3.5 font-bold text-neutral-900">{{ $p->name }}</td>
                            <td class="px-5 py-3.5 text-neutral-600">{{ $p->category }}</td>
                            <td class="px-5 py-3.5 hidden md:table-cell text-neutral-500">{{ $p->spec ?: '-' }}</td>
                            <td class="px-5 py-3.5 hidden md:table-cell text-neutral-500">{{ $p->unit }}</td>
                            <td class="px-5 py-3.5">
                                @if ($p->supply_type === 'supplier')
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-sky-100 text-sky-700">공급사 발송</span>
                                    <span class="text-xs text-neutral-500 ml-1">{{ optional($p->supplier)->name ?? '-' }}</span>
                                @else
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-mango-100 text-mango-700">본사 직공급</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right font-semibold text-mango-700">{{ number_format($p->store_price) }}원</td>
                            <td class="px-5 py-3.5 text-right hidden lg:table-cell">
                                @if ($p->supply_type === 'supplier' && $p->supply_price > 0)
                                    <span class="font-semibold text-neutral-700">{{ number_format($p->supply_price) }}원</span>
                                    <span class="block text-[11px] text-emerald-600">마진 {{ number_format($p->margin) }}@if ($p->store_price > 0) ({{ round($p->margin / $p->store_price * 100) }}%)@endif</span>
                                @else
                                    <span class="text-neutral-300">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                @php $ap = $p->approval_status; @endphp
                                <div class="flex flex-col items-center gap-1.5">
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full
                                        {{ $ap === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($ap === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-600') }}">
                                        {{ $p->approval_label }}@if ($p->registered_by === 'supplier') · 공급처등록@endif
                                    </span>
                                    @if ($ap !== 'approved')
                                        <div class="flex gap-1">
                                            <button type="button"
                                                    @click="openApprove('{{ route('portal.hq.products.approve', $p) }}', {{ \Illuminate\Support\Js::from($p->name) }}, {{ (int) $p->supply_price }}, {{ (int) $p->store_price }})"
                                                    class="rounded-md bg-emerald-500 hover:bg-emerald-600 text-white px-2 py-1 text-[11px] font-bold">승인</button>
                                            @if ($ap !== 'rejected')
                                                <form method="POST" action="{{ route('portal.hq.products.reject', $p) }}" onsubmit="return confirm('이 물품을 반려하시겠습니까?')">
                                                    @csrf @method('PATCH')
                                                    <button class="rounded-md bg-neutral-100 hover:bg-rose-50 text-rose-600 px-2 py-1 text-[11px] font-bold">반려</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-center hidden md:table-cell">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $p->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-400' }}">{{ $p->is_active ? '노출' : '숨김' }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex justify-end gap-2">
                                    <button type="button"
                                            @click="openEdit('{{ route('portal.hq.products.update', $p) }}', {{ Illuminate\Support\Js::from([
                                                'name' => $p->name, 'code' => $p->code, 'category' => $p->category, 'spec' => $p->spec, 'unit' => $p->unit,
                                                'store_price' => $p->store_price, 'tax_type' => $p->tax_type ?: 'inc', 'sort_order' => $p->sort_order, 'is_active' => (bool) $p->is_active,
                                                'supply_type' => $p->supply_type, 'supplier_id' => $p->supplier_id, 'supply_price' => $p->supply_price,
                                                'image' => $p->image ? asset($p->image) : null,
                                            ]) }})"
                                            class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1.5 font-semibold">수정</button>
                                    <form method="POST" action="{{ route('portal.hq.products.destroy', $p) }}" onsubmit="return confirm('삭제하시겠습니까? 단가·재고 이력도 함께 삭제됩니다.')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg text-rose-600 hover:bg-rose-50 px-3 py-1.5 font-semibold">삭제</button>
                                    </form>
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

{{-- 완제품 신규/수정 모달 (기본정보만) --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/50" @click="open=false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100 sticky top-0 bg-white rounded-t-2xl">
            <h2 class="text-lg font-extrabold text-neutral-900" x-text="mode === 'edit' ? '품목 수정' : '새 품목 추가'"></h2>
            <button type="button" @click="open=false" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-neutral-100 text-neutral-500">✕</button>
        </div>
        <form :action="action" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="_method" :value="method">
            <input type="hidden" name="_mode" :value="mode">
            <input type="hidden" name="_action" :value="action">
            <input type="hidden" name="remove_image" :value="removeImage ? 1 : 0">

            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">품목명</label>
                    <input type="text" name="name" x-model="form.name" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">품목코드 <span class="text-neutral-400 font-normal">(자동 채번)</span></label>
                    <input type="text" :value="form.code || '신규 저장 시 자동 생성'" readonly class="w-full rounded-xl border-neutral-200 bg-neutral-100 text-neutral-500 font-mono cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">대분류</label>
                    <select name="category" x-model="form.category" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                        @foreach ($finishedCategories as $cat)<option value="{{ $cat }}">{{ $cat }}</option>@endforeach
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
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">판매가 (원) <span class="text-neutral-400 font-normal">매장 판매가</span></label>
                    <input type="number" name="store_price" x-model="form.store_price" required min="0" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">부가세 구분 <span class="text-neutral-400 font-normal">세금계산서</span></label>
                    <select name="tax_type" x-model="form.tax_type" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                        <option value="inc">과세 (부가세 포함)</option>
                        <option value="exc">과세 (부가세 별도)</option>
                        <option value="exempt">면세</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">정렬 순서</label>
                    <input type="number" name="sort_order" x-model="form.sort_order" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <label class="flex items-center gap-2 text-sm font-medium text-neutral-700 mt-7">
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded text-mango-500 focus:ring-mango-400"> 매장 발주 화면에 노출
                </label>
            </div>

            {{-- 공급 구분 --}}
            <div class="rounded-xl bg-neutral-50 border border-neutral-100 p-4 space-y-4">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-2">공급 구분</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 rounded-lg border px-3 py-2.5 cursor-pointer transition"
                               :class="form.supply_type === 'hq' ? 'border-mango-400 bg-mango-50 text-mango-700 font-bold' : 'border-neutral-200 text-neutral-600'">
                            <input type="radio" name="supply_type" value="hq" x-model="form.supply_type" class="text-mango-500 focus:ring-mango-400"> 본사 직공급
                        </label>
                        <label class="flex items-center gap-2 rounded-lg border px-3 py-2.5 cursor-pointer transition"
                               :class="form.supply_type === 'supplier' ? 'border-sky-400 bg-sky-50 text-sky-700 font-bold' : 'border-neutral-200 text-neutral-600'">
                            <input type="radio" name="supply_type" value="supplier" x-model="form.supply_type" class="text-sky-500 focus:ring-sky-400"> 공급사 발송
                        </label>
                    </div>
                    <p class="text-[11px] text-neutral-400 mt-2">* «공급사 발송» 선택 시, 매장 발주가 <b>본사</b>와 지정한 <b>공급처</b>로 동시에 전달되어 양쪽에서 발주 정보를 확인합니다.</p>
                </div>

                <div class="grid md:grid-cols-2 gap-4" x-show="form.supply_type === 'supplier'" x-cloak>
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-1.5">공급처</label>
                        <select name="supplier_id" x-model="form.supplier_id" :required="form.supply_type === 'supplier'"
                                class="w-full rounded-xl border-neutral-200 focus:border-sky-400 focus:ring-sky-400">
                            <option value="">공급처 선택…</option>
                            @foreach ($suppliers as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-1.5">원가 (원) <span class="text-neutral-400 font-normal">공급처 공급단가 (공급처 → 본사)</span></label>
                        <input type="number" name="supply_price" x-model="form.supply_price" min="0"
                               class="w-full rounded-xl border-neutral-200 focus:border-sky-400 focus:ring-sky-400">
                    </div>
                </div>
            </div>

            {{-- 품목 이미지 --}}
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">품목 이미지 <span class="text-neutral-400 font-normal">(jpg/png/webp, 최대 4MB)</span></label>
                <div class="flex items-center gap-4">
                    <div class="w-20 h-20 rounded-xl ring-1 ring-neutral-200 overflow-hidden bg-neutral-50 grid place-items-center shrink-0">
                        <template x-if="imgPreview">
                            <img :src="imgPreview" alt="" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!imgPreview && form.image && !removeImage">
                            <img :src="form.image" alt="" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!imgPreview && (!form.image || removeImage)">
                            <span class="text-2xl text-neutral-300">🍧</span>
                        </template>
                    </div>
                    <div class="flex-1 space-y-2">
                        <input type="file" name="image_file" x-ref="imageInput" accept="image/*" @change="pickImage($event)"
                               class="block w-full text-sm text-neutral-600 file:mr-3 file:rounded-lg file:border-0 file:bg-mango-100 file:px-3 file:py-1.5 file:text-mango-700 file:font-semibold hover:file:bg-mango-200 cursor-pointer">
                        <button type="button" @click="dropImage()" x-show="imgPreview || (form.image && !removeImage)"
                                class="text-xs font-semibold text-rose-600 hover:underline">이미지 삭제</button>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-7 py-3 transition" x-text="mode === 'edit' ? '수정 저장' : '등록'"></button>
                <button type="button" @click="open=false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 font-bold px-7 py-3 transition">취소</button>
            </div>
        </form>
    </div>
</div>

{{-- 물품 승인 모달 (매장 판매가 책정) --}}
<div x-show="approveOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/50" @click="approveOpen=false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
            <h2 class="text-lg font-extrabold text-neutral-900">물품 승인</h2>
            <button type="button" @click="approveOpen=false" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-neutral-100 text-neutral-500">✕</button>
        </div>
        <form :action="approveAction" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="_method" value="PATCH">
            <p class="text-sm text-neutral-600"><b x-text="approveName"></b> 물품을 승인합니다. 매장 판매가(출고가)를 책정해 주세요.</p>
            <div class="flex justify-between text-sm bg-neutral-50 rounded-lg px-3 py-2">
                <span class="text-neutral-500">공급가 (공급처 → 본사)</span>
                <span class="font-bold" x-text="approveSupply.toLocaleString() + '원'"></span>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">매장 판매가 (원)</label>
                <input type="number" name="store_price" x-model.number="approveStore" required min="0"
                       class="w-full rounded-xl border-neutral-200 focus:border-emerald-400 focus:ring-emerald-400">
            </div>
            <div class="flex gap-3 pt-1">
                <button class="rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-6 py-2.5 transition">승인하기</button>
                <button type="button" @click="approveOpen=false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 font-bold px-6 py-2.5 transition">취소</button>
            </div>
        </form>
    </div>
</div>

</div>

@include('portal.partials.crud-modal-script')
@endsection
