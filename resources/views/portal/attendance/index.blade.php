@extends('portal.layout')
@section('title', '출퇴근 관리')

@section('content')
<x-wms.page-head title="출퇴근 관리" subtitle="출근·퇴근을 등록하면 정직원이 승인합니다." icon="🕐" />

{{-- 출근/퇴근 버튼 --}}
<div class="rounded-2xl bg-neutral-900 text-white p-8 mb-6 text-center">
    <p class="text-white/60 text-sm mb-1">{{ now()->format('Y년 m월 d일 (D)') }}</p>
    @if ($open)
        <p class="text-2xl font-black mb-1">출근 중</p>
        <p class="text-white/70 text-sm mb-5">출근 {{ $open->clock_in_at->format('H:i') }} · 경과 {{ $open->clock_in_at->diffForHumans(null, true) }}</p>
        <form method="POST" action="{{ route('portal.attendance.clock_out') }}">
            @csrf
            <button type="submit" class="rounded-2xl bg-rose-500 hover:bg-rose-600 text-white font-black text-lg px-10 py-4 transition">🏃 퇴근하기</button>
        </form>
    @else
        <p class="text-2xl font-black mb-5">오늘도 화이팅!</p>
        <form method="POST" action="{{ route('portal.attendance.clock_in') }}">
            @csrf
            <button type="submit" class="rounded-2xl bg-mango-500 hover:bg-mango-600 text-white font-black text-lg px-10 py-4 transition">🟢 출근하기</button>
        </form>
    @endif
</div>

{{-- 내 출퇴근 기록 --}}
<x-wms.panel>
    <div class="px-5 py-3 border-b border-neutral-100 text-sm font-bold text-neutral-700">내 출퇴근 기록</div>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-5 py-3">근무일</th>
                <th class="text-left font-semibold px-5 py-3">출근</th>
                <th class="text-left font-semibold px-5 py-3">퇴근</th>
                <th class="text-right font-semibold px-5 py-3">근무시간</th>
                <th class="text-left font-semibold px-5 py-3">상태</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($records as $a)
                <tr class="hover:bg-neutral-50">
                    <td class="px-5 py-3 font-medium text-neutral-800">{{ $a->work_date->format('Y.m.d') }}</td>
                    <td class="px-5 py-3 text-neutral-600">{{ $a->clock_in_at->format('H:i') }}</td>
                    <td class="px-5 py-3 text-neutral-600">{{ $a->clock_out_at ? $a->clock_out_at->format('H:i') : '—' }}</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ $a->clock_out_at ? $a->hours().'시간' : '-' }}</td>
                    <td class="px-5 py-3">
                        @php $c = ['pending'=>'bg-amber-100 text-amber-700','approved'=>'bg-emerald-100 text-emerald-700','rejected'=>'bg-rose-100 text-rose-700'][$a->status] ?? 'bg-neutral-100 text-neutral-500'; @endphp
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $c }}">{{ $a->statusLabel() }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-neutral-400">출퇴근 기록이 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-wms.panel>
@endsection
