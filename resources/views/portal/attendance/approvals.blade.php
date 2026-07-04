@extends('portal.layout')
@section('title', '출근 승인')

@section('content')
@php
    $statusChip = ['pending'=>'bg-amber-100 text-amber-700','approved'=>'bg-emerald-100 text-emerald-700','rejected'=>'bg-rose-100 text-rose-700'];
    $approvable = collect($attendances->items())->where('status','pending')->filter(fn($a)=>$a->clock_out_at)->pluck('id')->values();
@endphp
<div x-data="{ picked: [], allIds: {{ \Illuminate\Support\Js::from($approvable) }} }">
<x-wms.page-head title="출근 승인" subtitle="출근·퇴근 시간을 확인하고 승인합니다. 여러 건을 한 번에 승인할 수 있습니다." icon="✅" />

{{-- 필터 --}}
<form method="GET" action="{{ route('portal.attendance.approvals') }}" class="flex flex-wrap items-end gap-3 mb-5">
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">상태</label>
        <select name="status" class="rounded-xl border-neutral-200 text-sm py-2">
            <option value="all" @selected($status==='all')>전체</option>
            <option value="pending" @selected($status==='pending')>승인대기</option>
            <option value="approved" @selected($status==='approved')>승인</option>
            <option value="rejected" @selected($status==='rejected')>반려</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">직원</label>
        <select name="user" class="rounded-xl border-neutral-200 text-sm py-2 min-w-[9rem]">
            <option value="">전체</option>
            @foreach ($parttimers as $p)
                <option value="{{ $p->id }}" @selected((int)$userId === $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">시작일</label>
        <input type="date" name="from" value="{{ $from }}" class="rounded-xl border-neutral-200 text-sm py-2">
    </div>
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">종료일</label>
        <input type="date" name="to" value="{{ $to }}" class="rounded-xl border-neutral-200 text-sm py-2">
    </div>
    <button type="submit" class="rounded-xl bg-neutral-800 hover:bg-neutral-900 text-white font-bold px-4 py-2.5 text-sm transition">조회</button>
    <a href="{{ route('portal.attendance.approvals') }}" class="rounded-xl border border-neutral-200 hover:bg-neutral-50 text-neutral-500 font-bold px-4 py-2.5 text-sm">초기화</a>
</form>

{{-- 출퇴근 일괄 승인 툴바 --}}
<div x-show="picked.length" x-cloak class="mb-3 flex items-center gap-3 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3">
    <span class="text-sm font-bold text-emerald-800">선택 <span x-text="picked.length"></span>건</span>
    <form method="POST" action="{{ route('portal.attendance.bulk_approve') }}" onsubmit="return confirm('선택한 출퇴근 기록을 일괄 승인할까요?')">
        @csrf
        <template x-for="id in picked" :key="id"><input type="hidden" name="attendance_ids[]" :value="id"></template>
        <button type="submit" class="rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-4 py-2 text-sm transition">✅ 선택 승인</button>
    </form>
    <button type="button" @click="picked = []" class="text-xs text-neutral-500 hover:underline">선택 해제</button>
</div>

{{-- 출퇴근 --}}
<x-wms.panel class="mb-6">
    <div class="px-5 py-3 border-b border-neutral-100 text-sm font-bold text-neutral-700">출퇴근</div>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="px-4 py-3 w-10"><input type="checkbox" @change="picked = $event.target.checked ? [...allIds] : []" class="rounded border-neutral-300 text-emerald-500 focus:ring-emerald-400"></th>
                <th class="text-left font-semibold px-5 py-3">직원</th>
                <th class="text-left font-semibold px-5 py-3">근무일</th>
                <th class="text-left font-semibold px-5 py-3">출근</th>
                <th class="text-left font-semibold px-5 py-3">퇴근</th>
                <th class="text-right font-semibold px-5 py-3">근무시간</th>
                <th class="text-left font-semibold px-5 py-3">상태</th>
                <th class="text-right font-semibold px-5 py-3 w-40">처리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($attendances as $a)
                <tr class="hover:bg-neutral-50">
                    <td class="px-4 py-3">
                        @if ($a->status === 'pending' && $a->clock_out_at)
                            <input type="checkbox" x-model.number="picked" value="{{ $a->id }}" class="rounded border-neutral-300 text-emerald-500 focus:ring-emerald-400">
                        @endif
                    </td>
                    <td class="px-5 py-3 font-bold text-neutral-900">{{ $a->user->name ?? '-' }}</td>
                    <td class="px-5 py-3 text-neutral-600">{{ $a->work_date->format('m.d (D)') }}</td>
                    <td class="px-5 py-3 text-neutral-600">{{ $a->clock_in_at->format('H:i') }}</td>
                    <td class="px-5 py-3 text-neutral-600">{{ $a->clock_out_at ? $a->clock_out_at->format('H:i') : '근무중' }}</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ $a->clock_out_at ? $a->hours().'시간' : '-' }}</td>
                    <td class="px-5 py-3"><span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $statusChip[$a->status] ?? '' }}">{{ $a->statusLabel() }}</span></td>
                    <td class="px-5 py-3 text-right">
                        @if ($a->status === 'pending' && $a->clock_out_at)
                            <div class="flex justify-end gap-1.5">
                                <form method="POST" action="{{ route('portal.attendance.approve', $a) }}">@csrf @method('PATCH')
                                    <button class="rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-3 py-1.5 text-xs">승인</button></form>
                                <form method="POST" action="{{ route('portal.attendance.reject', $a) }}">@csrf @method('PATCH')
                                    <button class="rounded-lg border border-neutral-200 hover:bg-neutral-50 text-neutral-500 font-bold px-3 py-1.5 text-xs">반려</button></form>
                            </div>
                        @elseif ($a->status === 'pending')
                            <span class="text-xs text-neutral-400">퇴근 전</span>
                        @else
                            <span class="text-xs text-neutral-400">처리완료</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-5 py-10 text-center text-neutral-400">출퇴근 기록이 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($attendances->hasPages())
        <div class="px-5 py-3 border-t border-neutral-100">{{ $attendances->links() }}</div>
    @endif
</x-wms.panel>

{{-- 휴무 --}}
<x-wms.panel>
    <div class="px-5 py-3 border-b border-neutral-100 text-sm font-bold text-neutral-700">휴무</div>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-5 py-3">직원</th>
                <th class="text-left font-semibold px-5 py-3">휴무일</th>
                <th class="text-left font-semibold px-5 py-3">사유</th>
                <th class="text-left font-semibold px-5 py-3">상태</th>
                <th class="text-right font-semibold px-5 py-3 w-40">처리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($leaves as $l)
                <tr class="hover:bg-neutral-50">
                    <td class="px-5 py-3 font-bold text-neutral-900">{{ $l->user->name ?? '-' }}</td>
                    <td class="px-5 py-3 text-neutral-600">{{ $l->leave_date->format('m.d (D)') }}</td>
                    <td class="px-5 py-3 text-neutral-500">{{ $l->reason ?: '-' }}</td>
                    <td class="px-5 py-3"><span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $statusChip[$l->status] ?? '' }}">{{ $l->statusLabel() }}</span></td>
                    <td class="px-5 py-3 text-right">
                        @if ($l->status === 'pending')
                            <div class="flex justify-end gap-1.5">
                                <form method="POST" action="{{ route('portal.leaves.approve', $l) }}">@csrf @method('PATCH')
                                    <button class="rounded-lg bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-3 py-1.5 text-xs">승인</button></form>
                                <form method="POST" action="{{ route('portal.leaves.reject', $l) }}">@csrf @method('PATCH')
                                    <button class="rounded-lg border border-neutral-200 hover:bg-neutral-50 text-neutral-500 font-bold px-3 py-1.5 text-xs">반려</button></form>
                            </div>
                        @else
                            <span class="text-xs text-neutral-400">처리완료</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-10 text-center text-neutral-400">휴무 신청이 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($leaves->hasPages())
        <div class="px-5 py-3 border-t border-neutral-100">{{ $leaves->links() }}</div>
    @endif
</x-wms.panel>
</div>
@endsection
