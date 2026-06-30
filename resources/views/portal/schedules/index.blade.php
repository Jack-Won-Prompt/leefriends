@extends('portal.layout')
@section('title', '일정 관리')

@section('content')
<div x-data="calendar(
        {{ \Illuminate\Support\Js::from($byDate) }},
        '{{ today()->format('Y-m-d') }}',
        @js($ym)
     )">
<x-wms.page-head title="일정 관리" subtitle="달력에서 날짜를 클릭해 일정을 등록·관리합니다. 오늘 일정은 상단 📅에 표시됩니다." icon="📅" />

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    {{-- 월 네비게이션 --}}
    <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100">
        <div class="flex items-center gap-2">
            <button type="button" @click="prev()" class="w-9 h-9 grid place-items-center rounded-lg bg-neutral-100 hover:bg-neutral-200 font-bold">‹</button>
            <span class="text-lg font-extrabold text-neutral-900 w-32 text-center" x-text="monthLabel"></span>
            <button type="button" @click="next()" class="w-9 h-9 grid place-items-center rounded-lg bg-neutral-100 hover:bg-neutral-200 font-bold">›</button>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="goToday()" class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1.5 text-xs font-bold">오늘</button>
            <button type="button" @click="addOn(today)" class="rounded-lg bg-mango-500 hover:bg-mango-600 text-white px-4 py-1.5 text-sm font-bold">＋ 일정 추가</button>
        </div>
    </div>

    {{-- 요일 헤더 --}}
    <div class="grid grid-cols-7 bg-neutral-50 text-center text-xs font-bold text-neutral-500 border-b border-neutral-100">
        <template x-for="(w, i) in ['일','월','화','수','목','금','토']" :key="w">
            <div class="py-2" :class="i === 0 ? 'text-rose-500' : (i === 6 ? 'text-sky-500' : '')" x-text="w"></div>
        </template>
    </div>

    {{-- 날짜 그리드 --}}
    <div class="grid grid-cols-7">
        <template x-for="(cell, idx) in cells" :key="idx">
            <div class="min-h-[110px] border-b border-r border-neutral-100 p-1.5 align-top"
                 :class="cell ? 'cursor-pointer hover:bg-mango-50/40' : 'bg-neutral-50/50'"
                 @click="cell && addOn(cell.date)">
                <template x-if="cell">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold"
                                  :class="cell.date === today ? 'w-6 h-6 grid place-items-center rounded-full bg-mango-500 text-white' : (idx % 7 === 0 ? 'text-rose-500' : (idx % 7 === 6 ? 'text-sky-500' : 'text-neutral-700'))"
                                  x-text="cell.day"></span>
                        </div>
                        <div class="mt-1 space-y-1">
                            <template x-for="it in cell.items" :key="it.id">
                                <button type="button" @click.stop="edit(it)"
                                        class="block w-full text-left truncate rounded px-1.5 py-0.5 text-[11px] font-bold"
                                        :class="chipClass(it.color)" x-text="it.title"></button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>

{{-- 등록/수정 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @keydown.escape.window="open = false">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6" @click.outside="open = false">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-extrabold text-neutral-900" x-text="mode === 'create' ? '일정 등록' : '일정 수정'"></h3>
            <button type="button" @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" :action="action()" class="space-y-4">
            @csrf
            <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PATCH"></template>
            <input type="hidden" name="ym" :value="form.date ? form.date.slice(0,7) : ''">
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">날짜 *</label>
                <input type="date" name="schedule_date" x-model="form.date" required
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">제목 *</label>
                <input type="text" name="title" x-model="form.title" required maxlength="100"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="예: 본사 정기 회의">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">내용</label>
                <textarea name="content" x-model="form.content" rows="3" maxlength="2000"
                          class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="세부 내용 (선택)"></textarea>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">색상</label>
                <div class="flex gap-2">
                    @foreach (\App\Models\Schedule::COLORS as $c)
                        <button type="button" @click="form.color = '{{ $c }}'"
                                class="w-7 h-7 rounded-full border-2 {{ ['mango'=>'bg-mango-500','sky'=>'bg-sky-500','emerald'=>'bg-emerald-500','rose'=>'bg-rose-500','violet'=>'bg-violet-500','neutral'=>'bg-neutral-400'][$c] }}"
                                :class="form.color === '{{ $c }}' ? 'border-neutral-900' : 'border-transparent'"></button>
                    @endforeach
                </div>
            </div>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition" x-text="mode === 'create' ? '등록' : '저장'"></button>
                <template x-if="mode === 'edit'">
                    <button type="button" @click="remove()" class="rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold px-4 py-2.5 text-sm">삭제</button>
                </template>
                <button type="button" @click="open = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
            </div>
        </form>
    </div>
</div>

{{-- 숨김 삭제 폼 --}}
<form id="schedule-delete" method="POST" x-ref="deleteForm" class="hidden">@csrf @method('DELETE')<input type="hidden" name="ym" :value="form.date ? form.date.slice(0,7) : ''"></form>
</div>

@push('scripts')
<script>
    function calendar(byDate, todayStr, ym) {
        const init = (ym && /^\d{4}-\d{2}$/.test(ym)) ? ym.split('-') : todayStr.split('-');
        return {
            schedules: byDate || {},
            today: todayStr,
            year: parseInt(init[0]),
            month: parseInt(init[1]) - 1, // 0-11
            open: false, mode: 'create',
            form: { id: null, date: '', title: '', content: '', color: 'mango' },
            get monthLabel() { return this.year + '년 ' + (this.month + 1) + '월'; },
            get cells() {
                const startDow = new Date(this.year, this.month, 1).getDay();
                const days = new Date(this.year, this.month + 1, 0).getDate();
                const cells = [];
                for (let i = 0; i < startDow; i++) cells.push(null);
                for (let d = 1; d <= days; d++) {
                    const ds = this.year + '-' + String(this.month + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
                    cells.push({ day: d, date: ds, items: this.schedules[ds] || [] });
                }
                while (cells.length % 7 !== 0) cells.push(null);
                return cells;
            },
            chipClass(c) {
                return ({
                    mango: 'bg-mango-100 text-mango-700', sky: 'bg-sky-100 text-sky-700',
                    emerald: 'bg-emerald-100 text-emerald-700', rose: 'bg-rose-100 text-rose-700',
                    violet: 'bg-violet-100 text-violet-700', neutral: 'bg-neutral-100 text-neutral-600',
                })[c] || 'bg-mango-100 text-mango-700';
            },
            prev() { if (this.month === 0) { this.month = 11; this.year--; } else this.month--; },
            next() { if (this.month === 11) { this.month = 0; this.year++; } else this.month++; },
            goToday() { const t = this.today.split('-'); this.year = parseInt(t[0]); this.month = parseInt(t[1]) - 1; },
            addOn(date) { this.mode = 'create'; this.form = { id: null, date: date, title: '', content: '', color: 'mango' }; this.open = true; },
            edit(it) { this.mode = 'edit'; this.form = { id: it.id, date: it.date, title: it.title, content: it.content || '', color: it.color || 'mango' }; this.open = true; },
            action() { return this.mode === 'create' ? '{{ route('portal.schedules.store') }}' : '{{ url('portal/schedules') }}/' + this.form.id; },
            remove() {
                if (!confirm('이 일정을 삭제할까요?')) return;
                const f = this.$refs.deleteForm;
                f.setAttribute('action', '{{ url('portal/schedules') }}/' + this.form.id);
                f.submit();
            },
        };
    }
</script>
@endpush
@endsection
