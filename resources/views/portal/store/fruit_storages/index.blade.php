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

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $fruits->map(fn ($f) => [
        'name' => (string) $f->name,
        'temp_c' => (string) $f->temp_c,
        'temp_f' => (string) $f->temp_f,
        'ventilation' => (string) $f->ventilation,
        'humidity' => (string) $f->humidity,
        'dehumidification' => (string) $f->dehumidification,
        'storage_period' => (string) $f->storage_period,
    ])->values();
@endphp

<x-wms.panel>
    <div id="fruitStoragesGrid"></div>
</x-wms.panel>

<div class="mt-6">{{ $fruits->links() }}</div>

@push('scripts')
<script>
(function () {
    ww.grid('fruitStoragesGrid', [
        { header: '제품', name: 'name', width: 150 },
        { header: '온도(°C)', name: 'temp_c', width: 100 },
        { header: '온도(°F)', name: 'temp_f', width: 100 },
        { header: '통기공(CMH)', name: 'ventilation', width: 120 },
        { header: '상대습도(%)', name: 'humidity', width: 120 },
        { header: '제습', name: 'dehumidification', width: 110 },
        { header: '보관기한', name: 'storage_period', width: 130 },
    ], @json($gridRows));
})();
</script>
@endpush
<p class="mt-4 text-xs text-neutral-400">※ 온도·보관기한은 거래·계절·숙성 상태·원산지에 따라 달라질 수 있는 일반 가이드라인입니다. (출처: ZIM 권장 가이드)</p>
@endsection
