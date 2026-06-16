@extends('portal.layout')
@section('title', '출고 관리')

@section('content')
<x-wms.page-head title="출고 관리" subtitle="매장별 출고 생성·송장 입력·출고확정" icon="🚚">
    <x-slot:actions>
        <a href="{{ route($routePrefix . '.shipments.create') }}" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">+ 출고 생성</a>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.filter :action="route($routePrefix . '.shipments.index')">
    <x-wms.field label="진행상태">
        <select name="status" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="all">전체</option>
            @foreach ($statuses as $key => $label)
                <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </x-wms.field>
    <x-wms.field label="매장">
        <select name="store" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="all">전체 매장</option>
            @foreach ($stores as $s)
                <option value="{{ $s->id }}" @selected((string) $store === (string) $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </x-wms.field>
</x-wms.filter>

<x-wms.toolbar :count="$shipments->total()" />

<x-wms.panel>
    @if ($shipments->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">출고 내역이 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">출고번호</th>
                        <th class="text-left font-semibold px-6 py-3">매장</th>
                        <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">품목</th>
                        <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">수량</th>
                        <th class="text-left font-semibold px-6 py-3 hidden lg:table-cell">택배사</th>
                        <th class="text-left font-semibold px-6 py-3">송장번호</th>
                        <th class="text-left font-semibold px-6 py-3">상태</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">생성일</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($shipments as $s)
                        <tr class="hover:bg-mango-50/40 transition cursor-pointer" onclick="location.href='{{ route($routePrefix . '.shipments.show', $s) }}'">
                            <td class="px-6 py-3.5 font-bold text-neutral-900 font-mono">{{ $s->shipment_no }}</td>
                            <td class="px-6 py-3.5">{{ $s->store->name ?? '-' }}</td>
                            <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ number_format($s->item_count) }}</td>
                            <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ number_format($s->total_qty) }}</td>
                            <td class="px-6 py-3.5 hidden lg:table-cell text-neutral-500">{{ $s->carrier ?: '-' }}</td>
                            <td class="px-6 py-3.5 font-mono text-neutral-600">{{ $s->tracking_no ?: '-' }}</td>
                            <td class="px-6 py-3.5">@include('portal.partials.lifecycle-status', ['status' => $s->status, 'label' => $s->status_label])</td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $s->created_at->format('Y.m.d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-wms.panel>

<div class="mt-5">{{ $shipments->links() }}</div>
@endsection
