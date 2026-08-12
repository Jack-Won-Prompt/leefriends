@extends('portal.layout')
@section('title', '블로그 관리')

@section('content')
<x-wms.page-head title="블로그 관리" subtitle="공식 네이버 블로그의 최신 글을 가져와 홈페이지에 노출합니다." icon="📝">
    <x-slot:actions>
        <form method="POST" action="{{ route('portal.hq.blog.sync') }}" onsubmit="return confirm('네이버 블로그에서 새 글을 가져올까요?')">
            @csrf
            <button class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">🔄 블로그 업데이트</button>
        </form>
    </x-slot:actions>
</x-wms.page-head>

<p class="text-sm text-neutral-500 mb-4">대상 블로그: <b class="text-neutral-700">{{ $blogId }}</b> · <a href="https://blog.naver.com/{{ $blogId }}" target="_blank" rel="noopener" class="text-mango-600 hover:underline">블로그 바로가기 →</a></p>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $posts->map(fn ($p) => [
        'thumbnail' => $p->thumbnail_url ?: '',
        'title' => $p->title,
        'summary' => $p->summary ?: '',
        'url' => $p->url,
        'posted_at' => $p->posted_at?->format('Y-m-d') ?? '—',
        'sort_order' => (int) $p->sort_order,
        'is_active' => (bool) $p->is_active,
        'update_url' => route('portal.hq.blog.update', $p),
        'destroy_url' => route('portal.hq.blog.destroy', $p),
    ])->values();
@endphp

<x-wms.panel>
    <div id="hqBlogGrid"></div>
</x-wms.panel>

<div class="mt-6">{{ $posts->links() }}</div>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const hidden = (n, v) => { const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; return i; };
    ww.grid('hqBlogGrid', [
        { header: '썸네일', name: 'thumbnail', width: 90, sortable: false, exportable: false,
          renderer: (v) => {
              if (v) { const i = document.createElement('img'); i.src = v; i.className = 'w-14 h-14 rounded-lg object-cover bg-neutral-100'; i.alt = ''; i.referrerPolicy = 'no-referrer'; return i; }
              return ww.el('div', 'w-14 h-14 rounded-lg bg-neutral-100 grid place-items-center text-neutral-300', '📝');
          } },
        { header: '제목', name: 'title', width: 320,
          renderer: (v, row) => {
              const wrap = ww.el('div', '');
              const a = document.createElement('a'); a.href = row.url; a.target = '_blank'; a.rel = 'noopener';
              a.className = 'font-bold text-neutral-900 hover:text-mango-600 line-clamp-1'; a.textContent = v;
              wrap.appendChild(a);
              if (row.summary) wrap.appendChild(ww.el('p', 'text-xs text-neutral-400 line-clamp-1 mt-0.5', row.summary));
              return wrap;
          } },
        { header: '작성일', name: 'posted_at', width: 120 },
        { header: '정렬', name: 'sort_order', width: 130, exportable: false,
          renderer: (v, row) => {
              const f = document.createElement('form'); f.method = 'POST'; f.action = row.update_url; f.className = 'flex items-center gap-2';
              f.appendChild(hidden('_token', CSRF)); f.appendChild(hidden('_method', 'PATCH'));
              f.appendChild(hidden('is_active', row.is_active ? 1 : 0));
              const n = document.createElement('input'); n.type = 'number'; n.name = 'sort_order'; n.value = row.sort_order; n.className = 'w-16 rounded-lg border-neutral-200 text-sm py-1.5'; f.appendChild(n);
              const b = document.createElement('button'); b.textContent = '저장'; b.className = 'rounded-lg bg-neutral-100 hover:bg-neutral-200 px-2.5 py-1.5 font-semibold text-xs whitespace-nowrap'; f.appendChild(b);
              return f;
          } },
        { header: '노출', name: 'is_active', width: 90, exportable: false,
          renderer: (v, row) => {
              const f = document.createElement('form'); f.method = 'POST'; f.action = row.update_url;
              f.appendChild(hidden('_token', CSRF)); f.appendChild(hidden('_method', 'PATCH'));
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
