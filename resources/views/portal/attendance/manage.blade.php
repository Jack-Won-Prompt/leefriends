@extends('portal.layout')
@section('title', $user->name.' 출퇴근 관리')

@section('content')
@php $statusChip = ['pending'=>'bg-amber-100 text-amber-700','approved'=>'bg-emerald-100 text-emerald-700','rejected'=>'bg-rose-100 text-rose-700']; @endphp
<div x-data="{
        open: false,
        form: { id: null, work_date: '', clock_in: '', clock_out: '', approve: false, isNew: false },
        openNew(date) { this.form = { id: null, work_date: date, clock_in: '', clock_out: '', approve: true, isNew: true }; this.open = true; },
        openEdit(rec) { this.form = { id: rec.id, work_date: rec.work_date, clock_in: rec.clock_in, clock_out: rec.clock_out || '', approve: rec.status !== 'approved', isNew: false }; this.open = true; },
        action() { return this.form.isNew ? '{{ route('portal.attendance.manage_store', $user) }}' : '{{ url('portal/attendance') }}/' + this.form.id + '/times'; },
     }">

<x-wms.page-head :title="$user->name.' 출퇴근 관리'" :subtitle="'시급 '.number_format($user->hourly_wage).'원 · 날짜별 출퇴근 시간 등록·수정·승인'" icon="🕐">
    <x-slot:actions>
        <button type="button" @click="openNew('{{ now()->format('Y-m-d') }}')" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 출퇴근 등록</button>
        <a href="{{ route('portal.wages.index', ['from' => $from, 'to' => $to]) }}"
           class="inline-flex items-center gap-1 rounded-xl border border-neutral-200 hover:bg-neutral-50 font-bold px-4 py-2 text-sm">← 급여</a>
    </x-slot:actions>
</x-wms.page-head>

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
                <th class="text-left font-semibold px-5 py-3 w-40">날짜</th>
                <th class="text-left font-semibold px-5 py-3">출근</th>
                <th class="text-left font-semibold px-5 py-3">퇴근</th>
                <th class="text-left font-semibold px-5 py-3 w-32">근무시간</th>
                <th class="text-right font-semibold px-5 py-3">일당</th>
                <th class="text-left font-semibold px-5 py-3">상태</th>
                <th class="text-right font-semibold px-5 py-3 w-24">관리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($records as $a)
                @php $rec = ['id'=>$a->id,'work_date'=>$a->work_date->format('Y-m-d'),'clock_in'=>$a->clock_in_at->format('H:i'),'clock_out'=>$a->clock_out_at?->format('H:i'),'status'=>$a->status]; @endphp
                <tr class="hover:bg-neutral-50">
                    <td class="px-5 py-3 font-bold text-neutral-900">{{ $a->work_date->format('Y.m.d (D)') }}</td>
                    <td class="px-5 py-3 text-neutral-600">{{ $a->clock_in_at->format('H:i') }}</td>
                    <td class="px-5 py-3 text-neutral-600">{{ $a->clock_out_at ? $a->clock_out_at->format('H:i') : '—' }}</td>
                    <td class="px-5 py-3">
                        {{-- 근무시간(0시간) 클릭 → 팝업으로 출퇴근 등록/수정 --}}
                        <button type="button" @click="openEdit({{ \Illuminate\Support\Js::from($rec) }})"
                                class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-bold {{ $a->clock_out_at ? 'bg-neutral-100 text-neutral-700 hover:bg-neutral-200' : 'bg-amber-100 text-amber-700 hover:bg-amber-200' }}">
                            🕐 {{ $a->clock_out_at ? $a->hours().'시간' : '0시간' }}
                        </button>
                    </td>
                    <td class="px-5 py-3 text-right tabular-nums font-semibold">{{ $a->clock_out_at ? number_format($a->wage()).'원' : '-' }}</td>
                    <td class="px-5 py-3"><span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $statusChip[$a->status] ?? '' }}">{{ $a->statusLabel() }}</span></td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        @if ($a->status !== 'approved' && $a->clock_out_at)
                            <form method="POST" action="{{ route('portal.attendance.approve', $a) }}" class="inline">@csrf @method('PATCH')
                                <button class="text-emerald-600 hover:underline text-xs font-bold">승인</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-5 py-12 text-center text-neutral-400">해당 기간 출퇴근 기록이 없습니다. <span class="text-mango-600 font-semibold cursor-pointer" @click="openNew('{{ now()->format('Y-m-d') }}')">출퇴근 등록</span>으로 추가하세요.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-wms.panel>

{{-- 출퇴근 시간 등록/수정 팝업 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4 overflow-y-auto" @click.self="open = false">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl max-h-[90vh] overflow-y-auto my-auto">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900" x-text="form.isNew ? '출퇴근 등록' : '출퇴근 시간 수정'"></h2>
            <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" :action="action()" class="p-5 space-y-4">
            @csrf
            <template x-if="! form.isNew"><input type="hidden" name="_method" value="PATCH"></template>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">날짜 *</label>
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
            <label class="inline-flex items-center gap-1.5 text-sm text-neutral-600">
                <input type="checkbox" name="approve" value="1" x-model="form.approve" class="rounded border-neutral-300 text-emerald-500"> 저장 시 승인 처리
            </label>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition" x-text="form.isNew ? '등록' : '저장'"></button>
                <button type="button" @click="open = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
