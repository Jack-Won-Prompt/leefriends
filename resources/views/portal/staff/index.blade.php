@extends('portal.layout')
@section('title', '직원 관리')

@section('content')
@php($meId = auth()->id())
<div x-data="{
        open: false, mode: 'create',
        form: { id: null, name: '', email: '', phone: '', password: '' },
        openCreate() { this.mode = 'create'; this.form = { id: null, name: '', email: '', phone: '', password: '' }; this.open = true; },
        openEdit(u) { this.mode = 'edit'; this.form = { id: u.id, name: u.name, email: u.email, phone: u.phone || '', password: '' }; this.open = true; },
        action() { return this.mode === 'create' ? '{{ route('portal.staff.store') }}' : '{{ url('portal/staff') }}/' + this.form.id; },
     }">

<x-wms.page-head title="직원 관리" subtitle="우리 조직 소속 직원의 로그인 계정을 등록·관리합니다. 이메일이 로그인 ID입니다." icon="👥">
    <x-slot:actions>
        <button type="button" @click="openCreate()" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 직원 등록</button>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.panel>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-6 py-3">이름</th>
                <th class="text-left font-semibold px-6 py-3">이메일 (로그인 ID)</th>
                <th class="text-left font-semibold px-6 py-3">휴대폰</th>
                <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">등록일</th>
                <th class="text-right font-semibold px-6 py-3 w-32">관리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($staff as $u)
                <tr class="hover:bg-mango-50/40">
                    <td class="px-6 py-3.5 font-bold text-neutral-900">
                        {{ $u->name }}
                        @if ($u->id === $meId)<span class="ml-1 text-[11px] font-bold px-1.5 py-0.5 rounded bg-mango-100 text-mango-700">나</span>@endif
                    </td>
                    <td class="px-6 py-3.5 text-neutral-600">{{ $u->email }}</td>
                    <td class="px-6 py-3.5 text-neutral-500">{{ $u->phone ?: '-' }}</td>
                    <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $u->created_at->format('Y.m.d') }}</td>
                    <td class="px-6 py-3.5 text-right whitespace-nowrap">
                        <button type="button" @click="openEdit({ id: {{ $u->id }}, name: {{ Illuminate\Support\Js::from($u->name) }}, email: {{ Illuminate\Support\Js::from($u->email) }}, phone: {{ Illuminate\Support\Js::from($u->phone) }} })" class="text-mango-600 hover:text-mango-700 text-xs font-bold mr-2">수정</button>
                        @if ($u->id !== $meId)
                            <form method="POST" action="{{ route('portal.staff.destroy', $u) }}" class="inline" onsubmit="return confirm('«{{ $u->name }}» 직원 계정을 삭제할까요? 로그인이 즉시 차단됩니다.')">
                                @csrf @method('DELETE')
                                <button class="text-rose-500 hover:text-rose-600 text-xs font-bold">삭제</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-neutral-400">등록된 직원이 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-wms.panel>

{{-- 추가/수정 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="open = false">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900" x-text="mode === 'create' ? '직원 등록' : '직원 수정'"></h2>
            <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" :action="action()" class="p-5 space-y-4">
            @csrf
            <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PATCH"></template>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">이름 *</label>
                <input type="text" name="name" x-model="form.name" required maxlength="50"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="홍길동">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">이메일 (로그인 ID) *</label>
                <input type="email" name="email" x-model="form.email" required maxlength="100"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="staff@example.com">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">
                    <span x-text="mode === 'create' ? '임시 비밀번호 *' : '비밀번호 재설정'"></span>
                    <span class="text-neutral-400 font-normal" x-show="mode === 'edit'">(변경 시에만 입력)</span>
                </label>
                <input type="text" name="password" x-model="form.password" :required="mode === 'create'" minlength="4" maxlength="100" autocomplete="new-password"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="임시 비밀번호">
                <p class="text-[11px] text-neutral-400 mt-1">직원에게 전달 후 로그인하여 변경하도록 안내하세요.</p>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">휴대폰 번호 <span class="text-neutral-400 font-normal">(선택)</span></label>
                <input type="text" name="phone" x-model="form.phone" maxlength="30"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="010-0000-0000">
            </div>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition" x-text="mode === 'create' ? '등록' : '저장'"></button>
                <button type="button" @click="open = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
