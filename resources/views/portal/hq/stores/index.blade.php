@extends('portal.layout')
@section('title', '매장 관리')

@section('content')
<div x-data="{
        inviteOpen: {{ $errors->has('email') && old('_invite') ? 'true' : 'false' }},
        editOpen: false,
        editForm: { id: null, name: '', region: '', phone: '', email: '', postcode: '', address: '', address_detail: '', biz_no: '', ceo: '', biz_type: '', biz_class: '', is_active: true },
        openEdit(s) { this.editForm = Object.assign({ postcode:'', address:'', address_detail:'', biz_no:'', ceo:'', biz_type:'', biz_class:'' }, s); this.editOpen = true; },
     }">

<x-wms.page-head title="매장 관리" subtitle="가맹 매장을 이메일로 초대하고 계정 상태를 관리합니다" icon="🏪">
    <x-slot:actions>
        <button type="button" @click="inviteOpen = true"
                class="inline-flex items-center gap-1 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-4 py-2 text-sm transition">📧 이메일로 매장 초대</button>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.toolbar :count="$stores->total()" />

<x-wms.panel>
    @if ($stores->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">등록된 매장이 없습니다. «이메일로 매장 초대»로 추가해 주세요.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">매장명</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">지역</th>
                    <th class="text-left font-semibold px-6 py-3">연락처</th>
                    <th class="text-left font-semibold px-6 py-3 hidden lg:table-cell">이메일</th>
                    <th class="text-center font-semibold px-6 py-3">계정상태</th>
                    <th class="text-center font-semibold px-6 py-3 w-20">관리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($stores as $st)
                    <tr class="hover:bg-mango-50/40 transition {{ $st->is_active ? '' : 'opacity-50' }}">
                        <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $st->name }}</td>
                        <td class="px-6 py-3.5 hidden md:table-cell text-neutral-500">{{ $st->region ?: '-' }}</td>
                        <td class="px-6 py-3.5 text-neutral-600">{{ $st->phone ?: '-' }}</td>
                        <td class="px-6 py-3.5 hidden lg:table-cell text-neutral-500">{{ $st->email ?: '-' }}</td>
                        <td class="px-6 py-3.5 text-center">
                            @php $acc = $st->account; @endphp
                            @if ($acc && ! $acc->invite_token)
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">활성</span>
                            @elseif ($acc && $acc->invite_token)
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">초대됨 · 대기</span>
                                    <form method="POST" action="{{ route('portal.hq.stores.reinvite', $st) }}">@csrf
                                        <button class="text-[11px] font-semibold text-emerald-600 hover:underline">재발송</button>
                                    </form>
                                </div>
                            @else
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-400">계정 없음</span>
                                    @if ($st->email)
                                        <form method="POST" action="{{ route('portal.hq.stores.reinvite', $st) }}">@csrf
                                            <button class="text-[11px] font-semibold text-emerald-600 hover:underline">초대 메일 발송</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-3.5 text-center whitespace-nowrap">
                            <button type="button"
                                    @click="openEdit({ id: {{ $st->id }}, name: {{ Illuminate\Support\Js::from($st->name) }}, region: {{ Illuminate\Support\Js::from($st->region) }}, phone: {{ Illuminate\Support\Js::from($st->phone) }}, email: {{ Illuminate\Support\Js::from($st->email) }}, postcode: {{ Illuminate\Support\Js::from($st->postcode) }}, address: {{ Illuminate\Support\Js::from($st->address) }}, address_detail: {{ Illuminate\Support\Js::from($st->address_detail) }}, biz_no: {{ Illuminate\Support\Js::from($st->biz_no) }}, ceo: {{ Illuminate\Support\Js::from($st->ceo) }}, biz_type: {{ Illuminate\Support\Js::from($st->biz_type) }}, biz_class: {{ Illuminate\Support\Js::from($st->biz_class) }}, is_active: {{ $st->is_active ? 'true' : 'false' }} })"
                                    class="text-xs font-bold text-mango-600 hover:text-mango-700 mr-3">수정</button>
                            <form method="POST" action="{{ route('portal.hq.stores.destroy', $st) }}" class="inline"
                                  onsubmit="return confirm('매장 «{{ $st->name }}»을(를) 삭제할까요? 계정·채팅·재고도 함께 삭제되며 되돌릴 수 없습니다.')">
                                @csrf @method('DELETE')
                                <button class="text-xs font-bold text-rose-500 hover:text-rose-600">삭제</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-wms.panel>

<div class="mt-5">{{ $stores->links() }}</div>

{{-- ===== 매장 정보 수정 모달 ===== --}}
<div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/50" @click="editOpen=false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
            <h2 class="text-lg font-extrabold text-neutral-900">🏪 매장 정보 수정</h2>
            <button type="button" @click="editOpen=false" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-neutral-100 text-neutral-500">✕</button>
        </div>
        <form method="POST" :action="'{{ url('portal/hq/stores') }}/' + editForm.id" class="p-6 space-y-4">
            @csrf @method('PATCH')
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">매장명 <span class="text-rose-500">*</span></label>
                <input type="text" name="name" x-model="editForm.name" required maxlength="100"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">지역</label>
                    <input type="text" name="region" x-model="editForm.region" maxlength="50"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">연락처</label>
                    <input type="text" name="phone" x-model="editForm.phone" maxlength="30"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">이메일 <span class="text-neutral-400 font-normal">(초대·알림용)</span></label>
                <input type="email" name="email" x-model="editForm.email" maxlength="100"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">우편번호</label>
                    <input type="text" name="postcode" x-model="editForm.postcode" maxlength="20"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">주소 (배송지)</label>
                    <input type="text" name="address" x-model="editForm.address" maxlength="255"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">상세주소</label>
                <input type="text" name="address_detail" x-model="editForm.address_detail" maxlength="255"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            </div>
            <div class="rounded-xl bg-amber-50 border border-amber-100 p-3 space-y-3">
                <p class="text-xs font-bold text-amber-700">📄 사업자정보 <span class="font-normal text-amber-500">(세금계산서 발행용)</span></p>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-neutral-600 mb-1">사업자등록번호</label>
                        <input type="text" name="biz_no" x-model="editForm.biz_no" maxlength="20" placeholder="000-00-00000"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-neutral-600 mb-1">대표자</label>
                        <input type="text" name="ceo" x-model="editForm.ceo" maxlength="50"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-neutral-600 mb-1">업태</label>
                        <input type="text" name="biz_type" x-model="editForm.biz_type" maxlength="100"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-neutral-600 mb-1">종목</label>
                        <input type="text" name="biz_class" x-model="editForm.biz_class" maxlength="100"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    </div>
                </div>
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" x-model="editForm.is_active" class="rounded text-mango-500 focus:ring-mango-400">
                <span class="text-sm font-semibold text-neutral-700">활성 매장</span>
            </label>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition">저장</button>
                <button type="button" @click="editOpen=false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
            </div>
        </form>
    </div>
</div>

{{-- ===== 매장 이메일 초대 모달 ===== --}}
<div x-show="inviteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/50" @click="inviteOpen=false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
            <h2 class="text-lg font-extrabold text-neutral-900">📧 이메일로 매장 초대</h2>
            <button type="button" @click="inviteOpen=false" class="w-8 h-8 grid place-items-center rounded-lg hover:bg-neutral-100 text-neutral-500">✕</button>
        </div>
        <form method="POST" action="{{ route('portal.hq.stores.invite') }}" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="_invite" value="1">
            <p class="text-sm text-neutral-500">입력한 이메일로 초대 메일이 발송되며, 매장이 <b>비밀번호를 직접 설정</b>하면 포털을 사용할 수 있습니다.</p>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">매장명 <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ old('_invite') ? old('name') : '' }}" required class="w-full rounded-xl border-neutral-200 focus:border-emerald-400 focus:ring-emerald-400" placeholder="예: 리프렌즈 강남점">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">초대 이메일 <span class="text-rose-500">*</span></label>
                <input type="email" name="email" value="{{ old('_invite') ? old('email') : '' }}" required class="w-full rounded-xl border-neutral-200 focus:border-emerald-400 focus:ring-emerald-400" placeholder="store@example.com">
                @if ($errors->has('email') && old('_invite'))<p class="text-xs text-rose-500 mt-1">{{ $errors->first('email') }}</p>@endif
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">지역 <span class="text-neutral-400 font-normal">(선택)</span></label>
                    <input type="text" name="region" value="{{ old('_invite') ? old('region') : '' }}" class="w-full rounded-xl border-neutral-200 focus:border-emerald-400 focus:ring-emerald-400" placeholder="서울">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">연락처 <span class="text-neutral-400 font-normal">(선택)</span></label>
                    <input type="text" name="phone" value="{{ old('_invite') ? old('phone') : '' }}" class="w-full rounded-xl border-neutral-200 focus:border-emerald-400 focus:ring-emerald-400">
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
@endsection
