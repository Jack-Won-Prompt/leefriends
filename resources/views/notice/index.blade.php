@extends('layouts.app')

@section('title', '공지사항 · LEEFRIENDS')

@section('content')

@include('partials.page-hero', [
    'eyebrow' => 'NOTICE',
    'title' => '공지사항',
    'subtitle' => '리프렌즈의 새로운 소식과 이벤트를 확인하세요',
])

<section class="py-16 lg:py-20">
    <div class="max-w-4xl mx-auto px-5 lg:px-8">

        {{-- filter --}}
        <div class="flex flex-wrap justify-center gap-2.5 mb-10">
            @php $tabs = ['all' => '전체'] + $categories; @endphp
            @foreach ($tabs as $key => $label)
                <a href="{{ route('notice.index', $key === 'all' ? [] : ['cat' => $key]) }}"
                   class="px-5 py-2.5 rounded-full font-bold text-sm transition
                          {{ $category === $key ? 'bg-mango-500 text-white shadow-soft' : 'bg-mango-50 text-neutral-600 hover:bg-mango-100' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        @if ($notices->isEmpty())
            <p class="text-center text-neutral-400 py-20">등록된 공지사항이 없습니다.</p>
        @else
            <ul class="border-t-2 border-neutral-900">
                @foreach ($notices as $n)
                    <li class="border-b border-neutral-100">
                        <a href="{{ route('notice.show', $n) }}" class="flex items-center gap-4 py-5 px-2 hover:bg-mango-50/60 transition group">
                            <span class="shrink-0 text-xs font-bold px-3 py-1 rounded-full
                                {{ $n->category === 'event' ? 'bg-rose-100 text-rose-600' : ($n->category === 'news' ? 'bg-sky-100 text-sky-600' : 'bg-mango-100 text-mango-700') }}">
                                {{ $n->category_label }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-neutral-800 group-hover:text-mango-600 truncate transition">
                                    @if ($n->is_pinned)<span class="text-mango-500">📌</span> @endif{{ $n->title }}
                                </p>
                            </div>
                            <span class="shrink-0 text-sm text-neutral-400 hidden sm:block">{{ $n->published_at?->format('Y.m.d') }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-10">
                {{ $notices->links() }}
            </div>
        @endif
    </div>
</section>

@endsection
