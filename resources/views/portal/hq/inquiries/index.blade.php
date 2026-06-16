@extends('portal.layout')
@section('title', '창업 문의')

@php
    $badgeClass = [
        'new' => 'bg-rose-100 text-rose-700',
        'contacted' => 'bg-sky-100 text-sky-700',
        'done' => 'bg-emerald-100 text-emerald-700',
    ];
@endphp

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

<x-wms.panel>
    @if ($inquiries->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">접수된 창업 문의가 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">성함</th>
                        <th class="text-left font-semibold px-6 py-3">연락처</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">희망지역</th>
                        <th class="text-left font-semibold px-6 py-3 hidden lg:table-cell">예산</th>
                        <th class="text-left font-semibold px-6 py-3">상태</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">접수일</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($inquiries as $iq)
                        <tr class="hover:bg-mango-50/40 transition cursor-pointer" onclick="location.href='{{ route('portal.hq.inquiries.show', $iq) }}'">
                            <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $iq->name }}</td>
                            <td class="px-6 py-3.5">{{ $iq->phone }}</td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-500">{{ $iq->region ?: '-' }}</td>
                            <td class="px-6 py-3.5 hidden lg:table-cell text-neutral-500">{{ $iq->budget ?: '-' }}</td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-bold {{ $badgeClass[$iq->status] ?? 'bg-neutral-100 text-neutral-600' }}">{{ $iq->status_label }}</span>
                            </td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $iq->created_at->format('Y.m.d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-wms.panel>

@if ($inquiries->hasPages())
    <div class="mt-5">{{ $inquiries->links() }}</div>
@endif
@endsection
