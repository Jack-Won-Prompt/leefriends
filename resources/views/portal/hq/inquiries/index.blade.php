@extends('portal.layout')
@section('title', '창업 문의')

@section('content')
<x-wms.page-head title="창업 문의" subtitle="홈페이지에서 접수된 온라인 창업 문의를 확인하고 상담 상태를 관리합니다" icon="📨" />

{{-- 상태 필터 --}}
<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('portal.hq.inquiries.index') }}"
       class="px-4 py-2 rounded-full text-sm font-bold transition {{ $status === 'all' ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 hover:bg-mango-50 border border-neutral-200' }}">전체</a>
    @foreach ($statuses as $key => $label)
        <a href="{{ route('portal.hq.inquiries.index', ['status' => $key]) }}"
           class="px-4 py-2 rounded-full text-sm font-bold transition {{ $status === $key ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 hover:bg-mango-50 border border-neutral-200' }}">
            {{ $label }}@if ($key === 'new' && $newCount > 0) <span class="ml-1 text-rose-500">{{ $newCount }}</span>@endif
        </a>
    @endforeach
</div>

<x-wms.toolbar :count="$inquiries->total()" />

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $inquiries->map(fn ($iq) => [
        'name' => $iq->name,
        'phone' => $iq->phone,
        'region' => $iq->region ?: '-',
        'budget' => $iq->budget ?: '-',
        'status' => $iq->status,
        'status_label' => $iq->status_label,
        'created_at' => $iq->created_at->format('Y.m.d H:i'),
        'show_url' => route('portal.hq.inquiries.show', $iq),
    ])->values();
@endphp

<x-wwgrid-tabs gid="hqInquiriesGrid">
    <x-wms.panel>
        <div id="hqInquiriesGrid"></div>
    </x-wms.panel>
    @if ($inquiries->hasPages())
        <div class="mt-5">{{ $inquiries->links() }}</div>
    @endif
</x-wwgrid-tabs>

@push('scripts')
<script>
(function () {
    const BADGE = {
        new: 'bg-rose-100 text-rose-700',
        contacted: 'bg-sky-100 text-sky-700',
        done: 'bg-emerald-100 text-emerald-700',
    };
    const grid = ww.grid('hqInquiriesGrid', [
        { header: '성함', name: 'name', width: 130 },
        { header: '연락처', name: 'phone', width: 150 },
        { header: '희망지역', name: 'region', width: 150 },
        { header: '예산', name: 'budget', width: 150 },
        { header: '상태', name: 'status', width: 110, align: 'center',
          renderer: (v, row) => ww.badge(row.status_label, BADGE[v] || 'bg-neutral-100 text-neutral-600') },
        { header: '접수일', name: 'created_at', width: 150 },
    ], @json($gridRows));

    ww.bindRowDetail('hqInquiriesGrid', grid, 'show_url', 'name');
})();
</script>
@endpush
@endsection
