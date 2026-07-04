@extends('portal.layout')
@section('title', '과일 보관 가이드')

@section('content')
<x-wms.page-head title="과일 보관 가이드" subtitle="본사가 공유한 과일·채소 냉장/냉동 보관 가이드입니다." icon="🧊">
    <x-slot:actions>
        <form method="GET" class="flex items-center gap-2">
            <input type="text" name="q" value="{{ $q }}" placeholder="제품 검색" class="rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm py-2">
            <button class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-3.5 py-2 text-sm">검색</button>
        </form>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.panel>
    <div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-4 py-3">제품</th>
                <th class="text-left font-semibold px-4 py-3">온도(°C)</th>
                <th class="text-left font-semibold px-4 py-3 hidden lg:table-cell">온도(°F)</th>
                <th class="text-left font-semibold px-4 py-3 hidden lg:table-cell">통기공(CMH)</th>
                <th class="text-left font-semibold px-4 py-3">상대습도(%)</th>
                <th class="text-left font-semibold px-4 py-3 hidden md:table-cell">제습</th>
                <th class="text-left font-semibold px-4 py-3">보관기한</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($fruits as $f)
                <tr class="hover:bg-mango-50/40">
                    <td class="px-4 py-3 font-bold text-neutral-900 whitespace-nowrap">{{ $f->name }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $f->temp_c }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell whitespace-nowrap text-neutral-500">{{ $f->temp_f }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell whitespace-nowrap text-neutral-500">{{ $f->ventilation }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $f->humidity }}</td>
                    <td class="px-4 py-3 hidden md:table-cell whitespace-nowrap text-neutral-500">{{ $f->dehumidification }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">{{ $f->storage_period }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-12 text-center text-neutral-400">본사가 공유한 보관 가이드가 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</x-wms.panel>

<div class="mt-6">{{ $fruits->links() }}</div>
<p class="mt-4 text-xs text-neutral-400">※ 온도·보관기한은 거래·계절·숙성 상태·원산지에 따라 달라질 수 있는 일반 가이드라인입니다. (출처: ZIM 권장 가이드)</p>
@endsection
