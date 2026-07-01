@extends('portal.layout')
@section('title', '휴무 관리')

@section('content')
<div x-data="{ open: false }">
<x-wms.page-head title="휴무 관리" subtitle="휴무를 신청하면 정직원이 승인합니다." icon="🌴">
    <x-slot:actions>
        <button type="button" @click="open = true" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 휴무 신청</button>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.panel>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-5 py-3">휴무일</th>
                <th class="text-left font-semibold px-5 py-3">사유</th>
                <th class="text-left font-semibold px-5 py-3">상태</th>
                <th class="text-right font-semibold px-5 py-3 w-24">관리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($leaves as $l)
                <tr class="hover:bg-neutral-50">
                    <td class="px-5 py-3 font-medium text-neutral-800">{{ $l->leave_date->format('Y.m.d (D)') }}</td>
                    <td class="px-5 py-3 text-neutral-500">{{ $l->reason ?: '-' }}</td>
                    <td class="px-5 py-3">
                        @php $c = ['pending'=>'bg-amber-100 text-amber-700','approved'=>'bg-emerald-100 text-emerald-700','rejected'=>'bg-rose-100 text-rose-700'][$l->status] ?? 'bg-neutral-100 text-neutral-500'; @endphp
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $c }}">{{ $l->statusLabel() }}</span>
                    </td>
                    <td class="px-5 py-3 text-right">
                        @if ($l->status !== 'approved')
                            <form method="POST" action="{{ route('portal.leaves.destroy', $l) }}" onsubmit="return confirm('휴무 신청을 취소할까요?')">
                                @csrf @method('DELETE')
                                <button class="text-xs text-neutral-400 hover:text-rose-500 font-bold">취소</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-5 py-12 text-center text-neutral-400">휴무 신청 내역이 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-wms.panel>

{{-- 휴무 신청 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4 overflow-y-auto" @click.self="open = false">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto my-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900">휴무 신청</h2>
            <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" action="{{ route('portal.leaves.store') }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">휴무 날짜 *</label>
                <input type="date" name="leave_date" required min="{{ now()->format('Y-m-d') }}"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">사유 <span class="text-neutral-400 font-normal">(선택)</span></label>
                <input type="text" name="reason" maxlength="200"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="개인 사정 등">
            </div>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition">신청</button>
                <button type="button" @click="open = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
