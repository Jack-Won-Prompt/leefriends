@extends('portal.layout')
@section('title', 'FCM 알림 이력')

@section('content')
<x-wms.page-head title="FCM 알림 이력" subtitle="본사·매장에 발송된 앱/FCM 알림 내역을 수신자별로 확인합니다." icon="🔔" />

@php
    $typeLabel = fn ($t) => \App\Models\AppNotification::typeLabel($t);   // 앱과 공용 라벨
@endphp

{{-- 검색 필터 --}}
<form method="GET" action="{{ route('portal.hq.notification_logs.index') }}"
      class="flex flex-wrap items-end gap-3 mb-3 rounded-2xl bg-white shadow-sm border border-neutral-100 p-4">
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">구분</label>
        <select name="role" class="rounded-xl border-neutral-200 text-sm py-2 min-w-[7rem]">
            <option value="all" @selected($role==='all')>전체</option>
            <option value="hq" @selected($role==='hq')>본사</option>
            <option value="store" @selected($role==='store')>매장</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">매장</label>
        <select name="store" class="rounded-xl border-neutral-200 text-sm py-2 min-w-[10rem]">
            <option value="">전체 매장</option>
            @foreach ($stores as $s)
                <option value="{{ $s->id }}" @selected((int)$storeId === $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">유형</label>
        <select name="type" class="rounded-xl border-neutral-200 text-sm py-2 min-w-[9rem]">
            <option value="">전체</option>
            @foreach ($types as $t)
                <option value="{{ $t }}" @selected($type === $t)>{{ $typeLabel($t) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">시작일</label>
        <input type="date" name="from" value="{{ $from }}" class="rounded-xl border-neutral-200 text-sm py-2">
    </div>
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">종료일</label>
        <input type="date" name="to" value="{{ $to }}" class="rounded-xl border-neutral-200 text-sm py-2">
    </div>
    <div>
        <label class="block text-xs font-semibold text-neutral-500 mb-1">검색</label>
        <input type="text" name="q" value="{{ $q }}" placeholder="제목·내용" class="rounded-xl border-neutral-200 text-sm py-2">
    </div>
    <button type="submit" class="rounded-xl bg-neutral-800 hover:bg-neutral-900 text-white font-bold px-5 py-2.5 text-sm transition">조회</button>
    <a href="{{ route('portal.hq.notification_logs.index') }}" class="rounded-xl border border-neutral-200 hover:bg-neutral-50 text-neutral-500 font-bold px-4 py-2.5 text-sm">초기화</a>
</form>

{{-- 요약 --}}
<div class="grid grid-cols-3 gap-3 mb-3">
    <x-wms.stat label="전체 알림" :value="number_format($logs->total()).'건'" variant="default" icon="🔔" />
    <x-wms.stat label="본사 수신" :value="number_format($hqCount).'건'" variant="info" icon="🏢" />
    <x-wms.stat label="매장 수신" :value="number_format($storeCount).'건'" variant="success" icon="🏪" />
</div>

<x-wms.panel>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-4 py-3 whitespace-nowrap">발송시각</th>
                <th class="text-left font-semibold px-4 py-3">구분</th>
                <th class="text-left font-semibold px-4 py-3">수신자</th>
                <th class="text-left font-semibold px-4 py-3">매장</th>
                <th class="text-left font-semibold px-4 py-3">유형</th>
                <th class="text-left font-semibold px-4 py-3">제목 · 내용</th>
                <th class="text-center font-semibold px-4 py-3 whitespace-nowrap">읽음</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @forelse ($logs as $log)
                <tr class="hover:bg-mango-50/40 transition align-top">
                    <td class="px-4 py-3 text-neutral-500 whitespace-nowrap">{{ $log->created_at->format('Y.m.d H:i') }}</td>
                    <td class="px-4 py-3">
                        @if ($log->user_role === 'hq')
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-sky-100 text-sky-700">본사</span>
                        @else
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">매장</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-semibold text-neutral-800 whitespace-nowrap">{{ $log->user_name }}</td>
                    <td class="px-4 py-3 text-neutral-500 whitespace-nowrap">{{ $log->store_name ?: '—' }}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-600">{{ $typeLabel($log->type) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-bold text-neutral-900">{{ $log->title }}</p>
                        <p class="text-neutral-500 mt-0.5">{{ $log->body }}</p>
                    </td>
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        @if ($log->read_at)
                            <span class="text-xs text-emerald-600 font-bold">읽음</span>
                        @else
                            <span class="text-xs text-neutral-400">미읽음</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-16 text-center text-neutral-400">해당 조건의 알림 내역이 없습니다.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if ($logs->hasPages())
        <div class="px-5 py-3 border-t border-neutral-100">{{ $logs->links() }}</div>
    @endif
</x-wms.panel>
@endsection
