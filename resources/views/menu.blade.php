@extends('layouts.app')

@section('title', '메뉴 · LEEFRIENDS')

@section('content')

@include('partials.page-hero', [
    'eyebrow' => 'MENU',
    'title' => '리프렌즈 메뉴',
    'subtitle' => '제철 망고로 만드는 시그니처 빙수와 디저트',
])

<section class="py-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">

        {{-- category filter --}}
        <div class="flex flex-wrap justify-center gap-2.5 mb-12">
            @php
                $tabs = ['all' => '전체'] + $categories;
            @endphp
            @foreach ($tabs as $key => $label)
                <a href="{{ route('menu', $key === 'all' ? [] : ['cat' => $key]) }}"
                   class="px-5 py-2.5 rounded-full font-bold text-sm transition
                          {{ $category === $key ? 'bg-mango-500 text-white shadow-soft' : 'bg-mango-50 text-neutral-600 hover:bg-mango-100' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        @if ($menus->isEmpty())
            <p class="text-center text-neutral-400 py-20">해당 카테고리의 메뉴가 없습니다.</p>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach ($menus as $menu)
                    @include('partials.menu-card', ['menu' => $menu])
                @endforeach
            </div>
        @endif

        <p class="text-center text-sm text-neutral-400 mt-12">
            * 메뉴 이미지와 가격은 매장 사정에 따라 변경될 수 있습니다.
        </p>
    </div>
</section>

@endsection
