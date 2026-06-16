@extends('admin.layout')
@section('title', '매장 관리')

@section('content')
@php
    $hasErr = $errors->any();
    $initForm = $hasErr ? array_merge(['is_active' => (bool) old('is_active')], old()) : ['is_active' => true, 'region' => '서울'];
@endphp
<div x-data="Object.assign(crudModal({{ $hasErr ? 'true' : 'false' }}, {{ \Illuminate\Support\Js::from($initForm) }}), {
        mode: '{{ $hasErr ? old('_mode', 'create') : 'create' }}',
        action: '{{ $hasErr ? old('_action') : '' }}',
        method: '{{ $hasErr && old('_mode') === 'edit' ? 'PUT' : 'POST' }}',
     })">

<div class="flex justify-end mb-5">
    <button type="button" @click="openCreate('{{ route('admin.stores.store') }}', { is_active: true, region: '서울' })"
            class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2.5 transition">+ 새 매장 추가</button>
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    @if ($stores->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">등록된 매장이 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3 w-20">지역</th>
                        <th class="text-left font-semibold px-6 py-3">매장명</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">주소</th>
                        <th class="text-left font-semibold px-6 py-3 hidden lg:table-cell w-36">연락처</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell w-20">노출</th>
                        <th class="text-right font-semibold px-6 py-3 w-32">관리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($stores as $s)
                        <tr class="hover:bg-mango-50/40 transition">
                            <td class="px-6 py-3.5"><span class="text-xs font-bold px-2.5 py-1 rounded-full bg-mango-100 text-mango-700">{{ $s->region }}</span></td>
                            <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $s->name }}</td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-500">{{ $s->address }}</td>
                            <td class="px-6 py-3.5 hidden lg:table-cell text-neutral-500">{{ $s->phone }}</td>
                            <td class="px-6 py-3.5 hidden md:table-cell">
                                <span class="text-xs font-bold px-2 py-1 rounded-full {{ $s->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-400' }}">{{ $s->is_active ? '노출' : '숨김' }}</span>
                            </td>
                            <td class="px-6 py-3.5">
                                <div class="flex justify-end gap-2">
                                    <button type="button"
                                            @click="openEdit('{{ route('admin.stores.update', $s) }}', {{ Illuminate\Support\Js::from([
                                                'name' => $s->name, 'region' => $s->region,
                                                'address' => $s->address, 'postcode' => $s->postcode, 'address_detail' => $s->address_detail,
                                                'corp_postcode' => $s->corp_postcode, 'corp_address' => $s->corp_address, 'corp_address_detail' => $s->corp_address_detail,
                                                'phone' => $s->phone, 'hours' => $s->hours, 'lat' => $s->lat, 'lng' => $s->lng,
                                                'is_active' => (bool) $s->is_active,
                                            ]) }})"
                                            class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1.5 font-semibold">수정</button>
                                    <form method="POST" action="{{ route('admin.stores.destroy', $s) }}" onsubmit="return confirm('삭제하시겠습니까?')">
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
</div>

<div class="mt-6">{{ $stores->links() }}</div>

{{-- 매장 신규/수정 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/50" @click="open=false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100 sticky top-0 bg-white rounded-t-2xl">
            <h2 class="text-lg font-extrabold text-neutral-900" x-text="mode === 'edit' ? '매장 수정' : '새 매장 추가'"></h2>
            <button type="button" @click="open=false" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-neutral-100 text-neutral-500">✕</button>
        </div>
        <form :action="action" method="POST" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="_method" :value="method">
            <input type="hidden" name="_mode" :value="mode">
            <input type="hidden" name="_action" :value="action">

            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">매장명</label>
                    <input type="text" name="name" x-model="form.name" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">지역</label>
                    <input type="text" name="region" x-model="form.region" required list="regions" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                    <datalist id="regions">@foreach (['서울','경기','인천','부산','대구','대전','광주','울산','강원'] as $r)<option value="{{ $r }}">@endforeach</datalist>
                </div>
            </div>
            {{-- 배송 주소 (입고지) --}}
            <div class="rounded-xl border border-neutral-200 p-4 space-y-3">
                <p class="text-sm font-extrabold text-neutral-800">📦 배송 주소 <span class="text-neutral-400 font-normal">(입고지)</span></p>
                <div class="flex gap-2">
                    <input type="text" name="postcode" x-model="form.postcode" readonly placeholder="우편번호"
                           class="w-32 rounded-xl border-neutral-200 bg-neutral-50 text-sm">
                    <button type="button" @click="findAddress(d => { form.postcode = d.postcode; form.address = d.address })"
                            class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 text-sm">주소 검색</button>
                </div>
                <input type="text" name="address" x-model="form.address" required readonly placeholder="주소 검색 버튼을 눌러 주세요"
                       class="w-full rounded-xl border-neutral-200 bg-neutral-50 focus:border-mango-400 focus:ring-mango-400">
                <input type="text" name="address_detail" x-model="form.address_detail" placeholder="상세주소 입력"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
            </div>

            {{-- 법인 주소 --}}
            <div class="rounded-xl border border-neutral-200 p-4 space-y-3">
                <p class="text-sm font-extrabold text-neutral-800">🏢 법인 주소</p>
                <div class="flex gap-2">
                    <input type="text" name="corp_postcode" x-model="form.corp_postcode" readonly placeholder="우편번호"
                           class="w-32 rounded-xl border-neutral-200 bg-neutral-50 text-sm">
                    <button type="button" @click="findAddress(d => { form.corp_postcode = d.postcode; form.corp_address = d.address })"
                            class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 text-sm">주소 검색</button>
                </div>
                <input type="text" name="corp_address" x-model="form.corp_address" readonly placeholder="주소 검색 버튼을 눌러 주세요"
                       class="w-full rounded-xl border-neutral-200 bg-neutral-50 focus:border-mango-400 focus:ring-mango-400">
                <input type="text" name="corp_address_detail" x-model="form.corp_address_detail" placeholder="상세주소 입력"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
            </div>

            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">연락처</label>
                    <input type="text" name="phone" x-model="form.phone" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">운영시간</label>
                    <input type="text" name="hours" x-model="form.hours" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="매일 11:00 - 22:00">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">위도(lat)</label>
                    <input type="text" name="lat" x-model="form.lat" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">경도(lng)</label>
                    <input type="text" name="lng" x-model="form.lng" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm font-medium text-neutral-700">
                <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded text-mango-500 focus:ring-mango-400"> 홈페이지에 노출
            </label>

            <div class="flex gap-3 pt-2">
                <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-7 py-3 transition" x-text="mode === 'edit' ? '수정 저장' : '등록'"></button>
                <button type="button" @click="open=false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 font-bold px-7 py-3 transition">취소</button>
            </div>
        </form>
    </div>
</div>

</div>

@include('portal.partials.crud-modal-script')
@include('portal.partials.postcode-search')
@endsection
