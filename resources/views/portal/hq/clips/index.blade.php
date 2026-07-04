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

<x-wms.panel>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr class="whitespace-nowrap">
                <th class="text-left font-semibold px-5 py-3 w-24">썸네일</th>
                <th class="text-left font-semibold px-5 py-3">제목 / URL</th>
                <th class="text-left font-semibold px-5 py-3">정렬</th>
                <th class="text-left font-semibold px-5 py-3">노출</th>
                <th class="text-right font-semibold px-5 py-3">관리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($clips as $c)
                <tr class="hover:bg-mango-50/40 transition">
                    <td class="px-5 py-3">
                        @if ($c->thumbnail)
                            <img src="{{ $c->thumbnail_url }}" class="w-20 h-12 rounded-lg object-cover bg-neutral-100" alt="" referrerpolicy="no-referrer">
                        @else
                            <div class="w-20 h-12 rounded-lg bg-neutral-100 grid place-items-center text-neutral-300">🎬</div>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <form method="POST" action="{{ route('portal.hq.clips.update', $c) }}" class="flex flex-wrap items-center gap-2">
                            @csrf @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $c->is_active ? 1 : 0 }}">
                            <input type="hidden" name="sort_order" value="{{ $c->sort_order }}">
                            <input type="text" name="title" value="{{ $c->title }}" class="flex-1 min-w-[200px] rounded-lg border-neutral-200 text-sm py-1.5 font-bold">
                            <button class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-2.5 py-1.5 font-semibold text-xs whitespace-nowrap">저장</button>
                        </form>
                        <a href="{{ $c->url }}" target="_blank" rel="noopener" class="text-xs text-neutral-400 hover:text-mango-600 line-clamp-1 mt-1 block">{{ $c->url }}</a>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap">
                        <form method="POST" action="{{ route('portal.hq.clips.update', $c) }}" class="flex items-center gap-2">
                            @csrf @method('PATCH')
                            <input type="hidden" name="title" value="{{ $c->title }}">
                            <input type="hidden" name="is_active" value="{{ $c->is_active ? 1 : 0 }}">
                            <input type="number" name="sort_order" value="{{ $c->sort_order }}" class="w-16 rounded-lg border-neutral-200 text-sm py-1.5">
                            <button class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-2.5 py-1.5 font-semibold text-xs whitespace-nowrap">저장</button>
                        </form>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap">
                        <form method="POST" action="{{ route('portal.hq.clips.update', $c) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="title" value="{{ $c->title }}">
                            <input type="hidden" name="sort_order" value="{{ $c->sort_order }}">
                            <input type="hidden" name="is_active" value="{{ $c->is_active ? 0 : 1 }}">
                            <button class="inline-block text-xs font-bold px-2 py-1 rounded-full whitespace-nowrap {{ $c->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-400' }}">{{ $c->is_active ? '노출' : '숨김' }}</button>
                        </form>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap">
                        <div class="flex justify-end">
                            <form method="POST" action="{{ route('portal.hq.clips.destroy', $c) }}" onsubmit="return confirm('삭제하시겠습니까?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg text-rose-600 hover:bg-rose-50 px-3 py-1.5 font-semibold whitespace-nowrap">삭제</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-neutral-400">등록된 네이버 클립이 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-wms.panel>

<div class="mt-6">{{ $clips->links() }}</div>
@endsection
