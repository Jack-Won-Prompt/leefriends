@extends('portal.layout')
@section('title', '회원가입 승인')

@section('content')
<div x-data="{
        rejectOpen: false,
        rejectAction: '',
        rejectName: '',
        openReject(action, name) { this.rejectAction = action; this.rejectName = name; this.rejectOpen = true; },
     }">

<x-wms.page-head title="회원가입 승인" subtitle="자가 가입한 제품 구매자 · 공급자 신청을 검토하고 승인/반려합니다" icon="📝" />

<x-wms.toolbar :count="$pending->total()" />

<x-wms.panel>
    @if ($pending->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">승인 대기 중인 회원가입 신청이 없습니다.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">회원 종류</th>
                    <th class="text-left font-semibold px-6 py-3">상호</th>
                    <th class="text-left font-semibold px-6 py-3">담당자</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">연락처</th>
                    <th class="text-left font-semibold px-6 py-3 hidden lg:table-cell">이메일</th>
                    <th class="text-left font-semibold px-6 py-3 hidden lg:table-cell">신청일</th>
                    <th class="text-center font-semibold px-6 py-3 w-44">처리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($pending as $u)
                    @php $org = $u->role === 'store' ? $u->store : $u->supplier; @endphp
                    <tr class="hover:bg-mango-50/40 transition">
                        <td class="px-6 py-3.5">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold {{ $u->role === 'store' ? 'bg-sky-50 text-sky-600' : 'bg-violet-50 text-violet-600' }}">
                                {{ $u->role === 'store' ? '🛒' : '📦' }} {{ $u->signup_type_label }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 font-bold text-neutral-900">
                            {{ $org?->name ?? '-' }}
                            @if ($org?->biz_no)<span class="block text-xs font-normal text-neutral-400">{{ $org->biz_no }}</span>@endif
                        </td>
                        <td class="px-6 py-3.5 text-neutral-600">{{ $u->name }}</td>
                        <td class="px-6 py-3.5 hidden md:table-cell text-neutral-500">{{ $u->phone ?: '-' }}</td>
                        <td class="px-6 py-3.5 hidden lg:table-cell text-neutral-500">{{ $u->email }}</td>
                        <td class="px-6 py-3.5 hidden lg:table-cell text-neutral-400">{{ $u->created_at->format('Y-m-d') }}</td>
                        <td class="px-6 py-3.5">
                            <div class="flex items-center justify-center gap-2">
                                <form method="POST" action="{{ route('portal.hq.registrations.approve', $u) }}"
                                      onsubmit="return confirm('{{ $org?->name }} 님의 가입을 승인하시겠습니까?')">
                                    @csrf
                                    <button class="rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-3 py-1.5 text-xs transition">승인</button>
                                </form>
                                <button type="button"
                                        @click="openReject('{{ route('portal.hq.registrations.reject', $u) }}', '{{ $org?->name }}')"
                                        class="rounded-lg bg-neutral-100 hover:bg-rose-50 text-neutral-600 hover:text-rose-600 font-bold px-3 py-1.5 text-xs transition">반려</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</x-wms.panel>

@if ($pending->hasPages())
    <div class="mt-4">{{ $pending->links() }}</div>
@endif

{{-- 반려 사유 입력 모달 --}}
<div x-show="rejectOpen" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="rejectOpen = false">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl p-6">
        <h3 class="text-lg font-extrabold text-neutral-900 mb-1">가입 반려</h3>
        <p class="text-sm text-neutral-500 mb-4"><span class="font-bold" x-text="rejectName"></span> 님의 가입을 반려합니다.</p>
        <form method="POST" :action="rejectAction">
            @csrf
            <label class="block text-sm font-bold text-neutral-700 mb-1.5">반려 사유 <span class="font-normal text-neutral-400">(선택 · 신청자에게 메일로 안내됩니다)</span></label>
            <textarea name="reason" rows="3" maxlength="255"
                      class="w-full rounded-xl border-neutral-200 focus:border-rose-400 focus:ring-rose-400 text-sm"
                      placeholder="예) 사업자 정보 확인이 필요합니다."></textarea>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="rejectOpen = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2 text-sm transition">취소</button>
                <button class="rounded-xl bg-rose-500 hover:bg-rose-600 text-white font-bold px-4 py-2 text-sm transition">반려 처리</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
