@extends('portal.layout')
@section('title', '공지사항')

@section('content')
<div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }">

<x-wms.page-head title="공지사항" subtitle="매장·공급처에 공지를 작성해 발송합니다. 발송 즉시 실시간 알림이 전달됩니다." icon="📢">
    <x-slot:actions>
        <button type="button" @click="open = true"
                class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">✏️ 공지 작성</button>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.toolbar :count="$notices->total()" />

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $notices->map(fn ($n) => [
        'title' => $n->title,
        'is_pinned' => (bool) $n->is_pinned,
        'preview' => \Illuminate\Support\Str::limit(strip_tags($n->content), 80),
        'audience' => $n->audience,
        'audience_label' => $n->audience_label,
        'author' => optional($n->author)->name ?? '본사',
        'created_at' => $n->created_at->format('Y.m.d H:i'),
        'destroy_url' => route('portal.hq.notices.destroy', $n),
    ])->values();
@endphp

<x-wms.panel>
    <div id="hqNoticesGrid"></div>
</x-wms.panel>

@if ($notices->hasPages())
    <div class="mt-5">{{ $notices->links() }}</div>
@endif

{{-- 작성 모달 --}}
<div x-show="open" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/40 p-4" @click.self="open = false">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-neutral-100">
            <h2 class="font-extrabold text-neutral-900">✏️ 공지 작성</h2>
            <button @click="open = false" class="text-neutral-400 hover:text-neutral-600">✕</button>
        </div>
        <form method="POST" action="{{ route('portal.hq.notices.store') }}" class="p-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">대상 *</label>
                <div class="flex gap-2">
                    @foreach (['all' => '전체', 'store' => '매장', 'supplier' => '공급처'] as $key => $label)
                        <label class="flex-1">
                            <input type="radio" name="audience" value="{{ $key }}" @checked(old('audience', 'all') === $key) class="peer sr-only">
                            <span class="block text-center rounded-xl border border-neutral-200 px-3 py-2 text-sm font-bold text-neutral-600 cursor-pointer peer-checked:border-mango-400 peer-checked:bg-mango-50 peer-checked:text-mango-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">제목 *</label>
                <input type="text" name="title" value="{{ old('title') }}" maxlength="150" required
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="공지 제목">
            </div>
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">내용 *</label>
                <textarea name="content" rows="6" maxlength="5000" required
                          class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="공지 내용을 입력하세요">{{ old('content') }}</textarea>
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned')) class="rounded text-mango-500 focus:ring-mango-400">
                <span class="text-sm font-semibold text-neutral-700">상단 고정 📌</span>
            </label>
            @error('title')<p class="text-xs text-rose-500">{{ $message }}</p>@enderror
            @error('content')<p class="text-xs text-rose-500">{{ $message }}</p>@enderror
            <div class="flex gap-2 pt-1">
                <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition">발송하기</button>
                <button type="button" @click="open = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm transition">취소</button>
            </div>
        </form>
    </div>
</div>
</div>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const AUD_CLS = { all: 'bg-mango-100 text-mango-700', store: 'bg-emerald-100 text-emerald-700', supplier: 'bg-sky-100 text-sky-700' };
    ww.grid('hqNoticesGrid', [
        { header: '제목', name: 'title', width: 360,
          renderer: (v, row) => {
              const d = document.createElement('div');
              const top = document.createElement('div'); top.className = 'flex items-center gap-1.5';
              if (row.is_pinned) { const p = document.createElement('span'); p.className = 'text-mango-500'; p.title = '상단고정'; p.textContent = '📌'; top.appendChild(p); }
              const t = document.createElement('span'); t.className = 'font-bold text-neutral-900'; t.textContent = row.title; top.appendChild(t);
              d.appendChild(top);
              const pv = document.createElement('p'); pv.className = 'text-xs text-neutral-400 mt-0.5'; pv.textContent = row.preview; d.appendChild(pv);
              return d;
          } },
        { header: '대상', name: 'audience', width: 100, align: 'center',
          renderer: (v, row) => ww.badge(row.audience_label, AUD_CLS[v] || '') },
        { header: '작성자', name: 'author', width: 120 },
        { header: '발송일', name: 'created_at', width: 150 },
        { header: '관리', name: 'destroy_url', width: 90, align: 'center', sortable: false, exportable: false,
          renderer: (v, row) => {
              const form = document.createElement('form'); form.method = 'POST'; form.action = row.destroy_url;
              form.addEventListener('submit', (e) => { if (!confirm('이 공지를 삭제할까요?')) e.preventDefault(); });
              const t = document.createElement('input'); t.type = 'hidden'; t.name = '_token'; t.value = CSRF; form.appendChild(t);
              const m = document.createElement('input'); m.type = 'hidden'; m.name = '_method'; m.value = 'DELETE'; form.appendChild(m);
              const b = document.createElement('button'); b.type = 'submit'; b.textContent = '삭제';
              b.className = 'text-rose-500 hover:text-rose-600 text-xs font-bold'; form.appendChild(b);
              return form;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection
