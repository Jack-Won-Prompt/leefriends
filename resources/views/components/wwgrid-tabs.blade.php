{{-- 리스트/상세보기 탭 래퍼. 리스트 슬롯은 기본 슬롯, 상세는 iframe(?panel=1).
     표준 id 규칙({gid}-btnList 등)을 public/vendor/wwgrid/portal-grid.js(ww.*)가 사용한다.
     사용: <x-wwgrid-tabs gid="fooGrid"> ...그리드+페이지네이션... </x-wwgrid-tabs> --}}
@props(['gid', 'count' => null])

<div class="flex items-center gap-1 border-b border-neutral-200 mb-2">
    <button type="button" id="{{ $gid }}-btnList" onclick="ww.switchTab('{{ $gid }}','list')"
            class="px-4 py-2.5 text-sm font-extrabold border-b-2 border-mango-500 text-mango-600 -mb-px transition">📋 리스트@if (! is_null($count)) <span class="font-bold text-neutral-400">(합계 <span class="text-mango-600">{{ number_format($count) }}</span>건)</span>@endif</button>
    <button type="button" id="{{ $gid }}-btnDetail" onclick="ww.switchTab('{{ $gid }}','detail')" disabled
            class="px-4 py-2.5 text-sm font-extrabold border-b-2 border-transparent text-neutral-300 -mb-px transition">
        📄 상세보기<span id="{{ $gid }}-detailLabel" class="ml-1 font-bold"></span>
    </button>
</div>

<div id="{{ $gid }}-tabList">
    {{ $slot }}
</div>

<div id="{{ $gid }}-tabDetail" class="hidden">
    <div class="rounded-2xl bg-white shadow-sm border border-neutral-200 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-2 border-b border-neutral-100 bg-neutral-50">
            <span id="{{ $gid }}-detailTitle" class="font-extrabold text-sm text-neutral-900">상세</span>
            <div class="flex items-center gap-1.5">
                <a id="{{ $gid }}-full" href="#" target="_blank" class="rounded-lg border border-neutral-200 hover:bg-white px-2.5 py-1 text-xs font-bold text-neutral-500">새 탭 ↗</a>
                <button type="button" onclick="ww.switchTab('{{ $gid }}','list')" class="rounded-lg border border-neutral-200 hover:bg-white px-2.5 py-1 text-xs font-bold text-neutral-500">← 리스트</button>
            </div>
        </div>
        {{-- 상세 내용을 AJAX 로 직접 임베드(iframe 아님) --}}
        <div id="{{ $gid }}-detail" class="overflow-auto" style="max-height: calc(100vh - 150px);"></div>
    </div>
</div>
