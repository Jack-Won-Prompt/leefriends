@extends('layouts.app')

@section('title', '매장 안내 · LEEFRIENDS')

@section('content')

@include('partials.page-hero', [
    'eyebrow' => 'STORE',
    'title' => '매장 안내',
    'subtitle' => '가까운 리프렌즈 매장에서 신선한 망고빙수를 만나보세요',
])

<section class="py-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-5 lg:px-8">

        {{-- search / filter --}}
        <form method="GET" action="{{ route('store') }}" class="mb-10">
            <div class="flex flex-col md:flex-row gap-3 md:items-center">
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('store') }}"
                       class="px-4 py-2 rounded-full text-sm font-bold transition {{ $region === 'all' ? 'bg-mango-500 text-white' : 'bg-mango-50 text-neutral-600 hover:bg-mango-100' }}">전체</a>
                    @foreach ($regions as $r)
                        <a href="{{ route('store', ['region' => $r]) }}"
                           class="px-4 py-2 rounded-full text-sm font-bold transition {{ $region === $r ? 'bg-mango-500 text-white' : 'bg-mango-50 text-neutral-600 hover:bg-mango-100' }}">{{ $r }}</a>
                    @endforeach
                </div>
                <div class="md:ml-auto flex gap-2">
                    <input type="text" name="q" value="{{ $keyword }}" placeholder="매장명 또는 주소 검색"
                           class="rounded-full border-neutral-200 focus:border-mango-400 focus:ring-mango-400 px-5 py-2.5 text-sm w-full md:w-64">
                    <button class="rounded-full bg-neutral-900 text-white font-bold px-5 py-2.5 text-sm hover:bg-mango-600 transition">검색</button>
                </div>
            </div>
        </form>

        <p class="text-sm text-neutral-500 mb-6">총 <span class="font-black text-mango-600">{{ $stores->count() }}</span>개 매장</p>

        @if ($stores->isEmpty())
            <p class="text-center text-neutral-400 py-20">검색 결과가 없습니다.</p>
        @else
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($stores as $store)
                    <div class="rounded-3xl bg-white shadow-card overflow-hidden hover:shadow-soft transition group">
                        <div class="aspect-[16/9] overflow-hidden bg-mango-50 relative">
                            <img src="{{ asset($store->image ?: 'images/store/default.svg') }}" alt="{{ $store->name }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <span class="absolute top-3 left-3 bg-white/90 backdrop-blur text-mango-700 text-xs font-bold px-3 py-1 rounded-full">{{ $store->region }}</span>
                        </div>
                        <div class="p-6">
                            <h3 class="text-lg font-extrabold text-neutral-900 mb-3">{{ $store->name }}</h3>
                            <ul class="space-y-2 text-sm text-neutral-600">
                                <li class="flex gap-2"><span class="text-mango-500">📍</span><span>{{ $store->address }}</span></li>
                                @if ($store->phone)
                                    <li class="flex gap-2"><span class="text-mango-500">📞</span><a href="tel:{{ $store->phone }}" class="hover:text-mango-600">{{ $store->phone }}</a></li>
                                @endif
                                @if ($store->hours)
                                    <li class="flex gap-2"><span class="text-mango-500">🕒</span><span>{{ $store->hours }}</span></li>
                                @endif
                            </ul>
                            @if ($store->lat && $store->lng)
                                <a href="https://map.kakao.com/link/map/{{ $store->name }},{{ $store->lat }},{{ $store->lng }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1.5 mt-4 text-sm font-bold text-mango-700 hover:gap-2.5 transition-all">
                                    지도에서 보기 <span>→</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@endsection
