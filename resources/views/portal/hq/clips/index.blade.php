@extends('portal.layout')
@section('title', '네이버 클립 관리')

@section('content')
<x-wms.page-head title="네이버 클립 관리" subtitle="네이버 클립 URL을 등록하면 홈페이지 클립 섹션에 노출됩니다." icon="🎬" />

{{-- 클립 등록 --}}
<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-5 mb-6">
    <h2 class="font-extrabold text-neutral-900 mb-1">네이버 클립 추가</h2>
    <p class="text-xs text-neutral-400 mb-4">클립 URL 을 붙여넣고 등록하면 제목·썸네일을 자동으로 가져와 서버에 저장합니다. (예: https://clip.naver.com/...)</p>
    <form method="POST" action="{{ route('portal.hq.clips.store') }}" class="grid md:grid-cols-12 gap-3 items-end">
        @csrf
        <div class="md:col-span-12">
            <label class="block text-sm font-bold text-neutral-700 mb-1.5">클립 URL</label>
            <input type="url" name="url" required placeholder="https://clip.naver.com/..." value="{{ old('url') }}"
                   class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
            @error('url')<p class="text-xs text-rose-600 mt-1 font-semibold">{{ $message }}</p>@enderror
        </div>
        <div class="md:col-span-5">
            <label class="block text-sm font-bold text-neutral-700 mb-1.5">제목 <span class="text-neutral-400 font-normal">(비우면 자동)</span></label>
            <input type="text" name="title" value="{{ old('title') }}"
                   class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
        </div>
        <div class="md:col-span-5">
            <label class="block text-sm font-bold text-neutral-700 mb-1.5">썸네일 URL <span class="text-neutral-400 font-normal">(비우면 자동)</span></label>
            <input type="url" name="thumbnail" value="{{ old('thumbnail') }}" placeholder="자동 추출 실패 시 이미지 URL 직접 입력"
                   class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
        </div>
        <div class="md:col-span-2">
            <button class="w-full rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2.5 transition">＋ 등록</button>
        </div>
    </form>
</div>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $clips->map(fn ($c) => [
        'thumbnail' => $c->thumbnail ? $c->thumbnail_url : '',
        'title' => $c->title,
        'url' => $c->url,
        'sort_order' => (int) $c->sort_order,
        'is_active' => (bool) $c->is_active,
        'update_url' => route('portal.hq.clips.update', $c),
        'destroy_url' => route('portal.hq.clips.destroy', $c),
    ])->values();
@endphp

<x-wms.panel>
    <div id="hqClipsGrid"></div>
</x-wms.panel>

<div class="mt-6">{{ $clips->links() }}</div>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const hidden = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; return i; };
    ww.grid('hqClipsGrid', [
        { header: '썸네일', name: 'thumbnail', width: 110, sortable: false, exportable: false,
          renderer: (v) => {
              if (v) { const i = document.createElement('img'); i.src = v; i.className = 'w-20 h-12 rounded-lg object-cover bg-neutral-100'; i.alt = ''; i.referrerPolicy = 'no-referrer'; return i; }
              return ww.el('div', 'w-20 h-12 rounded-lg bg-neutral-100 grid place-items-center text-neutral-300', '🎬');
          } },
        { header: '제목 / URL', name: 'title', width: 340, exportable: false,
          renderer: (v, row) => {
              const wrap = ww.el('div', '');
              const f = document.createElement('form'); f.method = 'POST'; f.action = row.update_url; f.className = 'flex flex-wrap items-center gap-2';
              f.appendChild(hidden('_token', CSRF)); f.appendChild(hidden('_method', 'PATCH'));
              f.appendChild(hidden('is_active', row.is_active ? 1 : 0));
              f.appendChild(hidden('sort_order', row.sort_order));
              const ti = document.createElement('input'); ti.type = 'text'; ti.name = 'title'; ti.value = row.title; ti.className = 'flex-1 min-w-[200px] rounded-lg border-neutral-200 text-sm py-1.5 font-bold'; f.appendChild(ti);
              const b = document.createElement('button'); b.textContent = '저장'; b.className = 'rounded-lg bg-neutral-100 hover:bg-neutral-200 px-2.5 py-1.5 font-semibold text-xs whitespace-nowrap'; f.appendChild(b);
              wrap.appendChild(f);
              const a = document.createElement('a'); a.href = row.url; a.target = '_blank'; a.rel = 'noopener';
              a.className = 'text-xs text-neutral-400 hover:text-mango-600 line-clamp-1 mt-1 block'; a.textContent = row.url;
              wrap.appendChild(a);
              return wrap;
          } },
        { header: '정렬', name: 'sort_order', width: 130, exportable: false,
          renderer: (v, row) => {
              const f = document.createElement('form'); f.method = 'POST'; f.action = row.update_url; f.className = 'flex items-center gap-2';
              f.appendChild(hidden('_token', CSRF)); f.appendChild(hidden('_method', 'PATCH'));
              f.appendChild(hidden('title', row.title));
              f.appendChild(hidden('is_active', row.is_active ? 1 : 0));
              const n = document.createElement('input'); n.type = 'number'; n.name = 'sort_order'; n.value = row.sort_order; n.className = 'w-16 rounded-lg border-neutral-200 text-sm py-1.5'; f.appendChild(n);
              const b = document.createElement('button'); b.textContent = '저장'; b.className = 'rounded-lg bg-neutral-100 hover:bg-neutral-200 px-2.5 py-1.5 font-semibold text-xs whitespace-nowrap'; f.appendChild(b);
              return f;
          } },
        { header: '노출', name: 'is_active', width: 90, exportable: false,
          renderer: (v, row) => {
              const f = document.createElement('form'); f.method = 'POST'; f.action = row.update_url;
              f.appendChild(hidden('_token', CSRF)); f.appendChild(hidden('_method', 'PATCH'));
              f.appendChild(hidden('title', row.title));
              f.appendChild(hidden('sort_order', row.sort_order));
              f.appendChild(hidden('is_active', row.is_active ? 0 : 1));
              const b = document.createElement('button');
              b.className = 'inline-block text-xs font-bold px-2 py-1 rounded-full whitespace-nowrap ' + (row.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-400');
              b.textContent = row.is_active ? '노출' : '숨김'; f.appendChild(b);
              return f;
          } },
        { header: '관리', name: 'destroy_url', width: 100, align: 'right', sortable: false, exportable: false,
          renderer: (v, row) => {
              const wrap = ww.el('div', 'flex justify-end');
              const f = document.createElement('form'); f.method = 'POST'; f.action = row.destroy_url;
              f.addEventListener('submit', (e) => { if (!confirm('삭제하시겠습니까?')) e.preventDefault(); });
              f.appendChild(hidden('_token', CSRF)); f.appendChild(hidden('_method', 'DELETE'));
              const b = document.createElement('button'); b.textContent = '삭제'; b.className = 'rounded-lg text-rose-600 hover:bg-rose-50 px-3 py-1.5 font-semibold whitespace-nowrap'; f.appendChild(b);
              wrap.appendChild(f);
              return wrap;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection
