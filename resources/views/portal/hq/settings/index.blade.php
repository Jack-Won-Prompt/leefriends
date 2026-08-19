@extends('portal.layout')
@section('title', '환경 설정')

@section('content')
<x-wms.page-head title="환경 설정" subtitle="사이드바에 표시할 메뉴를 켜고 끕니다. 숨긴 메뉴는 좌측 메뉴에서 보이지 않습니다." icon="⚙️" />

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $rows->map(fn ($m) => [
        'group' => $m['group'],
        'label' => $m['label'],
        'route' => $m['route'],
        'hidden' => (bool) $m['hidden'],
        'locked' => (bool) $m['locked'],
    ])->values();
@endphp

<x-wms.panel :title="'메뉴 표시 설정 (총 '.count($gridRows).'개)'">
    <div id="settingsGrid"></div>
</x-wms.panel>

@push('scripts')
<script>
(function () {
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const TOGGLE_URL = @json(route('portal.hq.settings.toggle'));

    // 상단창 사이드바에서 해당 라벨의 메뉴 링크를 즉시 표시/숨김(새로고침 없이). 실패해도 다음 로드 시 서버가 반영.
    function syncSidebar(label, hidden) {
        try {
            const top = window.top; if (!top || top === window) return;
            const clean = (s) => (s || '').replace(/\s+/g, ' ').trim().replace(/\s+\d+$/, '').replace(/^[^가-힣A-Za-z0-9()]+/, '').trim();
            top.document.querySelectorAll('aside nav a').forEach((a) => {
                if (clean(a.textContent) === label) a.style.display = hidden ? 'none' : '';
            });
        } catch (e) {}
    }

    ww.grid('settingsGrid', [
        { header: '그룹', name: 'group', width: 160, renderer: (v) => ww.el('span', 'text-neutral-500', v) },
        { header: '메뉴', name: 'label', width: 220, renderer: (v) => ww.el('span', 'font-bold text-neutral-900', v) },
        { header: '라우트', name: 'route', width: 260, renderer: (v) => ww.el('span', 'text-xs text-neutral-400', v) },
        { header: '표시', name: 'hidden', width: 140, align: 'center', sortable: false, exportable: false,
          renderer: (v, row) => {
              if (row.locked) return ww.badge('항상 표시', 'bg-neutral-100 text-neutral-400');
              const btn = document.createElement('button');
              btn.type = 'button';
              const paint = () => {
                  btn.textContent = row.hidden ? '숨김' : '✓ 표시';
                  btn.className = 'rounded-lg font-bold px-4 py-1.5 text-xs transition ' +
                      (row.hidden ? 'bg-neutral-200 text-neutral-500 hover:bg-neutral-300' : 'bg-mango-500 text-white hover:bg-mango-600');
              };
              paint();
              btn.addEventListener('click', () => {
                  const next = !row.hidden;
                  btn.disabled = true;
                  fetch(TOGGLE_URL, {
                      method: 'POST',
                      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                      body: JSON.stringify({ route: row.route, hidden: next }),
                  }).then((r) => r.json()).then((d) => {
                      btn.disabled = false;
                      if (d && d.ok) {
                          row.hidden = d.hidden; paint();
                          syncSidebar(row.label, d.hidden);   // 상단창 사이드바 즉시 반영(새로고침 없이)
                          ww.toast('✅ 저장됨', row.label + (d.hidden ? ' 숨김' : ' 표시'));
                      }
                  }).catch(() => { btn.disabled = false; });
              });
              return btn;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection
