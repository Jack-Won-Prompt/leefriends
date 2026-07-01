@extends('portal.layout')
@section('title', '근태 승인')

@section('content')
<x-wms.page-head title="근태 승인" subtitle="소속 아르바이트의 출퇴근·휴무를 승인·반려합니다." icon="✅" />

@php $statusChip = ['pending'=>'bg-amber-100 text-amber-700','approved'=>'bg-emerald-100 text-emerald-700','rejected'=>'bg-rose-100 text-rose-700']; @endphp

{{-- 출퇴근 --}}
<x-wms.panel class="mb-6">
    <div class="px-5 py-3 border-b border-neutral-100 text-sm font-bold text-neutral-700">출퇴근</div>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
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
                <tr><td colspan="7" class="px-5 py-10 text-center text-neutral-400">출퇴근 기록이 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
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
</x-wms.panel>
@endsection
