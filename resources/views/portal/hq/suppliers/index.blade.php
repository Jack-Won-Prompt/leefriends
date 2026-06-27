@extends('portal.layout')
@section('title', '공급처 관리')

@section('content')
@php
    // 검증 실패 시 모달 자동 재오픈용 초기값
    $hasErr = $errors->any();
    $initForm = $hasErr ? array_merge(['is_active' => (bool) old('is_active')], old()) : ['is_active' => true];
@endphp
<div x-data="Object.assign(crudModal({{ $hasErr ? 'true' : 'false' }}, {{ \Illuminate\Support\Js::from($initForm) }}), {
        mode: '{{ $hasErr ? old('_mode', 'create') : 'create' }}',
        action: '{{ $hasErr ? old('_action') : '' }}',
        method: '{{ $hasErr && old('_mode') === 'edit' ? 'PUT' : 'POST' }}',
        inviteOpen: {{ $errors->has('email') && old('_invite') ? 'true' : 'false' }},
     })">

<div class="flex justify-end gap-2 mb-5">
    <button type="button" @click="inviteOpen = true"
            class="rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-5 py-2.5 transition">📧 이메일로 공급처 초대</button>
    <button type="button" @click="openCreate('{{ route('portal.hq.suppliers.store') }}', { is_active: true })"
            class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2.5 transition">+ 새 공급처 추가</button>
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    @if ($suppliers->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">등록된 공급처가 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">공급처명</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">사업자번호</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">대표자</th>
                        <th class="text-left font-semibold px-6 py-3">연락처</th>
                        <th class="text-right font-semibold px-6 py-3 hidden lg:table-cell">공급품목</th>
                        <th class="text-center font-semibold px-6 py-3">계정상태</th>
                        <th class="text-right font-semibold px-4 py-3 w-32 whitespace-nowrap">관리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($suppliers as $s)
                        <tr class="hover:bg-mango-50/40 transition {{ $s->is_active ? '' : 'opacity-50' }}">
                            <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $s->name }}</td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-500">{{ $s->biz_no ?: '-' }}</td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-500">{{ $s->ceo ?: '-' }}</td>
                            <td class="px-6 py-3.5 text-neutral-600">{{ $s->phone ?: '-' }}</td>
                            <td class="px-6 py-3.5 text-right hidden lg:table-cell text-neutral-500">{{ $s->products_count }}개</td>
                            <td class="px-6 py-3.5 text-center">
                                @php $acc = $s->account; @endphp
                                @if ($acc && ! $acc->invite_token)
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">활성</span>
                                @elseif ($acc && $acc->invite_token)
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">초대됨 · 대기</span>
                                        <form method="POST" action="{{ route('portal.hq.suppliers.reinvite', $s) }}">@csrf
                                            <button class="text-[11px] font-semibold text-emerald-600 hover:underline">재발송</button>
                                        </form>
                                    </div>
                                @else
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-400">계정 없음</span>
                                        @if ($s->email)
                                            <form method="POST" action="{{ route('portal.hq.suppliers.reinvite', $s) }}">@csrf
                                                <button class="text-[11px] font-semibold text-emerald-600 hover:underline">초대 메일 발송</button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="flex justify-end gap-1.5 whitespace-nowrap">
                                    <button type="button"
                                            @click="openEdit('{{ route('portal.hq.suppliers.update', $s) }}', {{ Illuminate\Support\Js::from([
                                                'name' => $s->name, 'biz_no' => $s->biz_no, 'ceo' => $s->ceo,
                                                'phone' => $s->phone, 'email' => $s->email,
                                                'address' => $s->address, 'postcode' => $s->postcode, 'address_detail' => $s->address_detail,
                                                'return_postcode' => $s->return_postcode, 'return_address' => $s->return_address, 'return_address_detail' => $s->return_address_detail,
                                                'is_active' => (bool) $s->is_active,
                                            ]) }})"
                                            class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1.5 font-semibold whitespace-nowrap">수정</button>
                                    <form method="POST" action="{{ route('portal.hq.suppliers.destroy', $s) }}" onsubmit="return confirm('삭제하시겠습니까?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg text-rose-600 hover:bg-rose-50 px-3 py-1.5 font-semibold whitespace-nowrap">삭제</button>
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

<div class="mt-6">{{ $suppliers->links() }}</div>

{{-- ===== 공급처 신규/수정 모달 ===== --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/50" @click="open=false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100 sticky top-0 bg-white rounded-t-2xl">
            <h2 class="text-lg font-extrabold text-neutral-900" x-text="mode === 'edit' ? '공급처 수정' : '새 공급처 추가'"></h2>
            <button type="button" @click="open=false" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-neutral-100 text-neutral-500">✕</button>
        </div>
        <form :action="action" method="POST" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="_method" :value="method">
            <input type="hidden" name="_mode" :value="mode">
            <input type="hidden" name="_action" :value="action">

            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">공급처명</label>
                    <input type="text" name="name" x-model="form.name" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">사업자등록번호</label>
                    <input type="text" name="biz_no" x-model="form.biz_no" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="000-00-00000">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">대표자</label>
                    <input type="text" name="ceo" x-model="form.ceo" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">연락처</label>
                    <input type="text" name="phone" x-model="form.phone" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">이메일</label>
                    <input type="email" name="email" x-model="form.email" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
                </div>
            </div>

            {{-- 법인 주소 --}}
            <div class="rounded-xl border border-neutral-200 p-4 space-y-3">
                <p class="text-sm font-extrabold text-neutral-800">🏢 법인 주소</p>
                <div class="flex gap-2">
                    <input type="text" name="postcode" x-model="form.postcode" readonly placeholder="우편번호" class="w-32 rounded-xl border-neutral-200 bg-neutral-50 text-sm">
                    <button type="button" @click="findAddress(d => { form.postcode = d.postcode; form.address = d.address })" class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 text-sm">주소 검색</button>
                </div>
                <input type="text" name="address" x-model="form.address" readonly placeholder="주소 검색 버튼을 눌러 주세요" class="w-full rounded-xl border-neutral-200 bg-neutral-50 focus:border-mango-400 focus:ring-mango-400">
                <input type="text" name="address_detail" x-model="form.address_detail" placeholder="상세주소 입력" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
            </div>

            {{-- 반품 주소 (매장 반품 시 보낼 곳) --}}
            <div class="rounded-xl border border-sky-200 bg-sky-50/40 p-4 space-y-3">
                <p class="text-sm font-extrabold text-neutral-800">↩️ 반품 주소 <span class="text-neutral-400 font-normal">(매장 반품 수령지)</span></p>
                <div class="flex gap-2">
                    <input type="text" name="return_postcode" x-model="form.return_postcode" readonly placeholder="우편번호" class="w-32 rounded-xl border-neutral-200 bg-white text-sm">
                    <button type="button" @click="findAddress(d => { form.return_postcode = d.postcode; form.return_address = d.address })" class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 text-sm">주소 검색</button>
                </div>
                <input type="text" name="return_address" x-model="form.return_address" readonly placeholder="미입력 시 법인 주소로 반품" class="w-full rounded-xl border-neutral-200 bg-white focus:border-mango-400 focus:ring-mango-400">
                <input type="text" name="return_address_detail" x-model="form.return_address_detail" placeholder="상세주소 입력" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
            </div>

            <label class="flex items-center gap-2 text-sm font-medium text-neutral-700">
                <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded text-mango-500 focus:ring-mango-400">
                활성 (품목 등록 가능)
            </label>

            <div class="flex gap-3 pt-2">
                <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-7 py-3 transition" x-text="mode === 'edit' ? '수정 저장' : '등록'"></button>
                <button type="button" @click="open=false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 font-bold px-7 py-3 transition">취소</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== 공급처 이메일 초대 모달 ===== --}}
<div x-show="inviteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/50" @click="inviteOpen=false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
            <h2 class="text-lg font-extrabold text-neutral-900">📧 이메일로 공급처 초대</h2>
            <button type="button" @click="inviteOpen=false" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-neutral-100 text-neutral-500">✕</button>
        </div>
        <form method="POST" action="{{ route('portal.hq.suppliers.invite') }}" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="_invite" value="1">
            <p class="text-sm text-neutral-500">입력한 이메일로 초대 메일이 발송되며, 공급처가 <b>비밀번호를 직접 설정</b>하면 포털을 사용할 수 있습니다.</p>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">공급처명 <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('_invite') ? old('name') : '' }}" required class="w-full rounded-xl border-neutral-200 focus:border-emerald-400 focus:ring-emerald-400" placeholder="예: 신선과일유통">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">초대 이메일 <span class="text-rose-500">*</span></label>
                <input type="email" name="email" value="{{ old('_invite') ? old('email') : '' }}" required class="w-full rounded-xl border-neutral-200 focus:border-emerald-400 focus:ring-emerald-400" placeholder="supplier@example.com">
                @if ($errors->has('email') && old('_invite'))<p class="text-xs text-rose-500 mt-1">{{ $errors->first('email') }}</p>@endif
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">사업자번호 <span class="text-neutral-400 font-normal">(선택)</span></label>
                    <input type="text" name="biz_no" value="{{ old('_invite') ? old('biz_no') : '' }}" class="w-full rounded-xl border-neutral-200 focus:border-emerald-400 focus:ring-emerald-400" placeholder="000-00-00000">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">대표자 <span class="text-neutral-400 font-normal">(선택)</span></label>
                    <input type="text" name="ceo" value="{{ old('_invite') ? old('ceo') : '' }}" class="w-full rounded-xl border-neutral-200 focus:border-emerald-400 focus:ring-emerald-400">
                </div>
            </div>
            <div class="flex gap-3 pt-1">
                <button class="rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-7 py-3 transition">초대 메일 발송</button>
                <button type="button" @click="inviteOpen=false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 font-bold px-7 py-3 transition">취소</button>
            </div>
        </form>
    </div>
</div>

</div>

@include('portal.partials.crud-modal-script')
@include('portal.partials.postcode-search')
@endsection
