@extends('portal.layout')
@section('title', $user->name.' 출퇴근 관리')

@section('content')
@php $statusChip = ['pending'=>'bg-amber-100 text-amber-700','approved'=>'bg-emerald-100 text-emerald-700','rejected'=>'bg-rose-100 text-rose-700']; @endphp
<div x-data="{ editId: null }">

<x-wms.page-head :title="$user->name.' 출퇴근 관리'" :subtitle="'시급 '.number_format($user->hourly_wage).'원 · 출퇴근 직접 입력·수정·승인'" icon="🕐">
    <x-slot:actions>
        <a href="{{ route('portal.wages.index', ['from' => $from, 'to' => $to]) }}"
           class="inline-flex items-center gap-1 rounded-xl border border-neutral-200 hover:bg-neutral-50 font-bold px-4 py-2 text-sm">← 급여</a>
    </x-slot:actions>
</x-wms.page-head>

{{-- 직접 입력 --}}
<x-wms.panel class="mb-5">
    <form method="POST" action="{{ route('portal.attendance.manage_store', $user) }}" class="p-5 flex flex-wrap items-end gap-3">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">근무일</label>
            <input type="date" name="work_date" value="{{ old('work_date', now()->format('Y-m-d')) }}" required class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">출근</label>
            <input type="time" name="clock_in" required class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <div>
            <label class="block text-xs font-semibold text-neutral-500 mb-1">퇴근 <span class="text-neutral-400 font-normal">(선택)</span></label>
            <input type="time" name="clock_out" class="rounded-xl border-neutral-200 text-sm py-2">
        </div>
        <button type="submit" class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2.5 text-sm transition">＋ 출퇴근 등록</button>
        <p class="w-full text-[11px] text-neutral-400">출근·퇴근을 모두 입력하면 등록 즉시 승인 처리됩니다.</p>
        @error('clock_out') <p class="w-full text-xs text-rose-500">{{ $message }}</p> @enderror
    </form>
</x-wms.panel>

{{-- 기간 필터 --}}
<form method="GET" class="flex flex-wrap items-end gap-2 mb-4">
    <input type="date" name="from" value="{{ $from }}" class="rounded-xl border-neutral-200 text-sm py-2">
    <span class="pb-2 text-neutral-400">~</span>
    <input type="date" name="to" value="{{ $to }}" class="rounded-xl border-neutral-200 text-sm py-2">
    <button class="rounded-xl bg-neutral-800 hover:bg-neutral-900 text-white font-bold px-4 py-2 text-sm">조회</button>
</form>

<x-wms.panel>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-5 py-3">근무일</th>
                <th class="text-left font-semibold px-5 py-3">출근</th>
                <th class="text-left font-semibold px-5 py-3">퇴근</th>
                <th class="text-right font-semibold px-5 py-3">근무시간</th>
                <th class="text-right font-semibold px-5 py-3">일당</th>
                <th class="text-left font-semibold px-5 py-3">상태</th>
                <th class="text-right font-semibold px-5 py-3 w-56">관리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($records as $a)
                <tr class="hover:bg-neutral-50">
                    <td class="px-5 py-3 font-medium text-neutral-800">{{ $a->work_date->format('Y.m.d (D)') }}</td>
                    <td class="px-5 py-3 text-neutral-600">{{ $a->clock_in_at->format('H:i') }}</td>
                    <td class="px-5 py-3 text-neutral-600">{{ $a->clock_out_at ? $a->clock_out_at->format('H:i') : '—' }}</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ $a->clock_out_at ? $a->hours().'시간' : '-' }}</td>
                    <td class="px-5 py-3 text-right tabular-nums font-semibold">{{ $a->clock_out_at ? number_format($a->wage()).'원' : '-' }}</td>
                    <td class="px-5 py-3"><span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $statusChip[$a->status] ?? '' }}">{{ $a->statusLabel() }}</span></td>
                    <td class="px-5 py-3 text-right">
                        <button type="button" @click="editId = (editId === {{ $a->id }} ? null : {{ $a->id }})" class="text-mango-600 hover:underline text-xs font-bold mr-2">시간수정</button>
                        @if ($a->status !== 'approved' && $a->clock_out_at)
                            <form method="POST" action="{{ route('portal.attendance.approve', $a) }}" class="inline">@csrf @method('PATCH')
                                <button class="text-emerald-600 hover:underline text-xs font-bold">승인</button></form>
                        @endif
                    </td>
                </tr>
                {{-- 시간 수정 행 --}}
                <tr x-show="editId === {{ $a->id }}" x-cloak class="bg-mango-50/40">
                    <td colspan="7" class="px-5 py-3">
                        <form method="POST" action="{{ route('portal.attendance.update_times', $a) }}" class="flex flex-wrap items-end gap-3">
                            @csrf @method('PATCH')
                            <div><label class="block text-xs font-semibold text-neutral-500 mb-1">근무일</label>
                                <input type="date" name="work_date" value="{{ $a->work_date->format('Y-m-d') }}" required class="rounded-xl border-neutral-200 text-sm py-2"></div>
                            <div><label class="block text-xs font-semibold text-neutral-500 mb-1">출근</label>
                                <input type="time" name="clock_in" value="{{ $a->clock_in_at->format('H:i') }}" required class="rounded-xl border-neutral-200 text-sm py-2"></div>
                            <div><label class="block text-xs font-semibold text-neutral-500 mb-1">퇴근</label>
                                <input type="time" name="clock_out" value="{{ $a->clock_out_at ? $a->clock_out_at->format('H:i') : '' }}" class="rounded-xl border-neutral-200 text-sm py-2"></div>
                            <label class="inline-flex items-center gap-1.5 pb-2 text-sm text-neutral-600"><input type="checkbox" name="approve" value="1" class="rounded border-neutral-300 text-emerald-500"> 저장 시 승인</label>
                            <button type="submit" class="rounded-xl bg-neutral-800 hover:bg-neutral-900 text-white font-bold px-4 py-2 text-sm">저장</button>
                            <button type="button" @click="editId = null" class="text-xs text-neutral-500 hover:underline pb-2">닫기</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-12 text-center text-neutral-400">해당 기간 출퇴근 기록이 없습니다. 위에서 직접 등록하세요.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-wms.panel>
</div>
@endsection
