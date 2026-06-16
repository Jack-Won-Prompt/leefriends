@extends('portal.layout')
@section('title', '매장 관리')

@section('content')
<div x-data="{ inviteOpen: {{ $errors->has('email') && old('_invite') ? 'true' : 'false' }} }">

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
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-wms.panel>

<div class="mt-5">{{ $stores->links() }}</div>

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
