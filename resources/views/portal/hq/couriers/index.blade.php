@extends('portal.layout')
@section('title', '택배사 관리')

@section('content')
<div x-data="{
        open: false, mode: 'create',
        form: { id: null, name: '', is_direct: false, is_active: true, sort_order: 0 },
        openCreate() { this.mode = 'create'; this.form = { id: null, name: '', is_direct: false, is_active: true, sort_order: 0 }; this.open = true; },
        openEdit(c) { this.mode = 'edit'; this.form = Object.assign({}, c); this.open = true; },
        action() { return this.mode === 'create' ? '{{ route('portal.hq.couriers.store') }}' : '{{ url('portal/hq/couriers') }}/' + this.form.id; },
     }">

<x-wms.page-head title="택배사 관리" subtitle="출고 확정 시 선택할 택배사를 등록·관리합니다. 직접 배송은 송장번호 없이 출고됩니다." icon="🚚">
    <x-slot:actions>
        <button type="button" @click="openCreate()" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 택배사 추가</button>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.panel>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-6 py-3 w-20">순서</th>
                <th class="text-left font-semibold px-6 py-3">택배사</th>
                <th class="text-left font-semibold px-6 py-3">구분</th>
                <th class="text-left font-semibold px-6 py-3">상태</th>
                <th class="text-right font-semibold px-6 py-3 w-32">관리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($couriers as $c)
                <tr class="hover:bg-mango-50/40">
                    <td class="px-6 py-3.5 text-neutral-400">{{ $c->sort_order }}</td>
                    <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $c->name }}</td>
                    <td class="px-6 py-3.5">
                        @if ($c->is_direct)
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-sky-100 text-sky-700">직접 배송</span>
                        @else
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-neutral-100 text-neutral-600">택배</span>
                        @endif
                    </td>
                    <td class="px-6 py-3.5">
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $c->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-400' }}">{{ $c->is_active ? '사용' : '미사용' }}</span>
                    </td>
                    <td class="px-6 py-3.5 text-right">
                        <button type="button" @click="openEdit({ id: {{ $c->id }}, name: {{ Illuminate\Support\Js::from($c->name) }}, is_direct: {{ $c->is_direct ? 'true' : 'false' }}, is_active: {{ $c->is_active ? 'true' : 'false' }}, sort_order: {{ (int) $c->sort_order }} })" class="text-mango-600 hover:text-mango-700 text-xs font-bold mr-2">수정</button>
                        <form method="POST" action="{{ route('portal.hq.couriers.destroy', $c) }}" class="inline" onsubmit="return confirm('이 택배사를 삭제할까요?')">
                            @csrf @method('DELETE')
                            <button class="text-rose-500 hover:text-rose-600 text-xs font-bold">삭제</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-neutral-400">등록된 택배사가 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-wms.panel>

{{-- 추가/수정 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="open = false">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900" x-text="mode === 'create' ? '택배사 추가' : '택배사 수정'"></h2>
            <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" :action="action()" class="p-5 space-y-4">
            @csrf
            <template x-if="mode === 'edit'"><input type="hidden" name="_method" value="PATCH"></template>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">택배사명 *</label>
                <input type="text" name="name" x-model="form.name" required maxlength="50"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="예: CJ대한통운">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <label class="flex items-center gap-2 rounded-xl border border-neutral-200 px-3 py-2.5">
                    <input type="checkbox" name="is_direct" value="1" x-model="form.is_direct" class="rounded text-sky-500 focus:ring-sky-400">
                    <span class="text-sm font-semibold text-neutral-700">직접 배송 <span class="text-neutral-400 font-normal">(송장 불필요)</span></span>
                </label>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">정렬 순서</label>
                    <input type="number" name="sort_order" x-model.number="form.sort_order"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                </div>
            </div>
            <template x-if="mode === 'edit'">
                <label class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="rounded text-mango-500 focus:ring-mango-400">
                    <span class="text-sm font-semibold text-neutral-700">사용 (출고 폼에 노출)</span>
                </label>
            </template>
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition" x-text="mode === 'create' ? '추가' : '저장'"></button>
                <button type="button" @click="open = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
            </div>
        </form>
    </div>
</div>
</div>
@endsection
