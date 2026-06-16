@extends('portal.layout')
@section('title', '매장 주문 변경')

@section('content')
@php $pending = $changes->where('acknowledged_at', null); @endphp
<x-wms.page-head title="매장 주문 변경" subtitle="매장이 수정/취소한 주문을 확인(반영)합니다" icon="⚠️">
    <x-slot:actions>
        @if ($changes->total() > 0)
            <form method="POST" action="{{ route('portal.order_changes.ack_all') }}">@csrf
                <button class="rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold px-4 py-2 text-sm">모두 반영</button>
            </form>
        @endif
    </x-slot:actions>
</x-wms.page-head>

<x-wms.toolbar :count="$changes->total()" label="변경 내역" />

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    @if ($changes->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">변경 내역이 없습니다.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">구분</th>
                    <th class="text-left font-semibold px-6 py-3">매장 / 주문</th>
                    <th class="text-left font-semibold px-6 py-3">내용</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">발생</th>
                    <th class="text-right font-semibold px-6 py-3 w-40">상태</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($changes as $c)
                    <tr class="{{ $c->acknowledged_at ? '' : 'bg-amber-50/50' }}">
                        <td class="px-6 py-3.5">
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $c->change_type === 'canceled' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-700' }}">{{ $c->type_label }}</span>
                        </td>
                        <td class="px-6 py-3.5">
                            <span class="font-bold text-neutral-900">{{ $c->store->name ?? '매장' }}</span>
                            <span class="block text-xs text-neutral-400">{{ $c->order_no }}</span>
                        </td>
                        <td class="px-6 py-3.5 text-neutral-600">{{ $c->summary }}</td>
                        <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $c->created_at->format('Y.m.d H:i') }}</td>
                        <td class="px-6 py-3.5 text-right">
                            @if ($c->acknowledged_at)
                                <span class="text-xs font-bold text-emerald-600">반영됨 · {{ $c->acknowledged_at->format('m.d H:i') }}</span>
                            @else
                                <form method="POST" action="{{ route('portal.order_changes.ack', $c) }}">@csrf
                                    <button class="rounded-lg bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 font-semibold">확인(반영)</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="mt-6">{{ $changes->links() }}</div>
@endsection
