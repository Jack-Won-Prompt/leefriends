@extends('admin.layout')
@section('title', '블로그 관리')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <p class="text-sm text-neutral-500">
        공식 네이버 블로그 <b class="text-neutral-700">{{ $blogId }}</b> 의 최신 글을 가져와 welcome 페이지에 노출합니다.
    </p>
    <form method="POST" action="{{ route('admin.blog.sync') }}"
          onsubmit="return confirm('네이버 블로그에서 새 글을 가져올까요?')">
        @csrf
        <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2.5 transition">🔄 블로그 업데이트</button>
    </form>
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    @if ($posts->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">아직 수집된 블로그 글이 없습니다. <b>블로그 업데이트</b> 버튼을 눌러주세요.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-5 py-3 w-20">썸네일</th>
                    <th class="text-left font-semibold px-5 py-3">제목</th>
                    <th class="text-left font-semibold px-5 py-3 hidden md:table-cell w-28">작성일</th>
                    <th class="text-left font-semibold px-5 py-3 w-24">정렬</th>
                    <th class="text-left font-semibold px-5 py-3 w-20">노출</th>
                    <th class="text-right font-semibold px-5 py-3 w-40">관리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($posts as $p)
                    <tr class="hover:bg-mango-50/40 transition">
                        <td class="px-5 py-3">
                            @if ($p->thumbnail_url)
                                <img src="{{ $p->thumbnail_url }}" class="w-14 h-14 rounded-lg object-cover bg-neutral-100" alt="" referrerpolicy="no-referrer">
                            @else
                                <div class="w-14 h-14 rounded-lg bg-neutral-100 grid place-items-center text-neutral-300">📝</div>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <a href="{{ $p->url }}" target="_blank" rel="noopener" class="font-bold text-neutral-900 hover:text-mango-600 line-clamp-1">{{ $p->title }}</a>
                            @if ($p->summary)<p class="text-xs text-neutral-400 line-clamp-1 mt-0.5">{{ $p->summary }}</p>@endif
                        </td>
                        <td class="px-5 py-3 hidden md:table-cell text-neutral-500">{{ $p->posted_at?->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <form method="POST" action="{{ route('admin.blog.update', $p) }}" class="flex items-center gap-2">
                                @csrf @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $p->is_active ? 1 : 0 }}">
                                <input type="number" name="sort_order" value="{{ $p->sort_order }}" class="w-16 rounded-lg border-neutral-200 text-sm py-1.5">
                                <button class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-2.5 py-1.5 font-semibold text-xs">저장</button>
                            </form>
                        </td>
                        <td class="px-5 py-3">
                            <form method="POST" action="{{ route('admin.blog.update', $p) }}">
                                @csrf @method('PATCH')
                                <input type="hidden" name="sort_order" value="{{ $p->sort_order }}">
                                <input type="hidden" name="is_active" value="{{ $p->is_active ? 0 : 1 }}">
                                <button class="text-xs font-bold px-2 py-1 rounded-full {{ $p->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-400' }}">{{ $p->is_active ? '노출' : '숨김' }}</button>
                            </form>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex justify-end">
                                <form method="POST" action="{{ route('admin.blog.destroy', $p) }}" onsubmit="return confirm('삭제하시겠습니까?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg text-rose-600 hover:bg-rose-50 px-3 py-1.5 font-semibold">삭제</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="mt-6">{{ $posts->links() }}</div>
@endsection
