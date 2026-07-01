@extends('portal.layout')
@section('title', '출퇴근 관리')

@section('content')
<div x-data="{
        open: false,
        form: { id: null, work_date: '', clock_in: '', clock_out: '', isNew: false },
        openNew() { this.form = { id: null, work_date: '{{ now()->format('Y-m-d') }}', clock_in: '', clock_out: '', isNew: true }; this.open = true; },
        openEdit(rec) { this.form = { id: rec.id, work_date: rec.work_date, clock_in: rec.clock_in, clock_out: rec.clock_out || '', isNew: false }; this.open = true; },
        action() { return this.form.isNew ? '{{ route('portal.attendance.store') }}' : '{{ url('portal/attendance') }}/' + this.form.id + '/own'; },
     }">
<x-wms.page-head title="출퇴근 관리" subtitle="출근·퇴근을 등록하면 정직원이 승인합니다. 버튼 외에 직접 추가·수정도 가능합니다." icon="🕐">
    <x-slot:actions>
        <button type="button" @click="openNew()" class="inline-flex items-center gap-1 rounded-xl border border-neutral-200 bg-white hover:bg-neutral-50 text-neutral-700 font-bold px-4 py-2 text-sm transition">＋ 출퇴근 추가</button>
    </x-slot:actions>
</x-wms.page-head>

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
                <th class="text-right font-semibold px-5 py-3 w-28">관리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($records as $a)
                @php $rec = ['id'=>$a->id,'work_date'=>$a->work_date->format('Y-m-d'),'clock_in'=>$a->clock_in_at->format('H:i'),'clock_out'=>$a->clock_out_at?->format('H:i')]; @endphp
                <tr class="hover:bg-neutral-50">
                    <td class="px-5 py-3 font-medium text-neutral-800">{{ $a->work_date->format('Y.m.d') }}</td>
                    <td class="px-5 py-3 text-neutral-600">{{ $a->clock_in_at->format('H:i') }}</td>
                    <td class="px-5 py-3 text-neutral-600">{{ $a->clock_out_at ? $a->clock_out_at->format('H:i') : '—' }}</td>
                    <td class="px-5 py-3 text-right tabular-nums">{{ $a->clock_out_at ? $a->hours().'시간' : '-' }}</td>
                    <td class="px-5 py-3">
                        @php $c = ['pending'=>'bg-amber-100 text-amber-700','approved'=>'bg-emerald-100 text-emerald-700','rejected'=>'bg-rose-100 text-rose-700'][$a->status] ?? 'bg-neutral-100 text-neutral-500'; @endphp
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $c }}">{{ $a->statusLabel() }}</span>
                    </td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        @if ($a->status === 'approved')
                            <span class="text-xs text-neutral-300">승인 완료</span>
                        @else
                            <button type="button" @click="openEdit({{ \Illuminate\Support\Js::from($rec) }})" class="text-mango-600 hover:underline text-xs font-bold mr-2">수정</button>
                            <form method="POST" action="{{ route('portal.attendance.destroy_own', $a) }}" class="inline" onsubmit="return confirm('이 출퇴근 기록을 삭제할까요?')">
                                @csrf @method('DELETE')
                                <button class="text-neutral-400 hover:text-rose-500 text-xs font-bold">삭제</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-12 text-center text-neutral-400">출퇴근 기록이 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-wms.panel>

{{-- 출퇴근 등록/수정 팝업 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4 overflow-y-auto" @click.self="open = false">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto my-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900" x-text="form.isNew ? '출퇴근 추가' : '출퇴근 수정'"></h2>
            <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" :action="action()" class="p-5 space-y-4">
            @csrf
            <template x-if="! form.isNew"><input type="hidden" name="_method" value="PATCH"></template>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">근무일 *</label>
                <input type="date" name="work_date" x-model="form.work_date" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">출근 *</label>
                    <input type="time" name="clock_in" x-model="form.clock_in" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">퇴근</label>
                    <input type="time" name="clock_out" x-model="form.clock_out" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
            </div>
            <p class="text-[11px] text-neutral-400" x-text="form.isNew ? '등록 후 정직원 승인으로 확정됩니다.' : '수정하면 다시 승인 대기 상태가 됩니다.'"></p>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition" x-text="form.isNew ? '등록' : '저장'"></button>
                <button type="button" @click="open = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
