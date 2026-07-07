@extends('portal.layout')
@section('title', '공급처 구매발주')

@section('content')
@php $chip = ['ordered'=>'bg-sky-100 text-sky-700','confirmed'=>'bg-amber-100 text-amber-700','received'=>'bg-emerald-100 text-emerald-700','canceled'=>'bg-neutral-100 text-neutral-400']; @endphp
<x-wms.page-head title="공급처 구매발주" subtitle="본사가 공급처에 재료·물품을 매입 발주합니다. 입고 처리하면 본사 재고에 반영됩니다." icon="🧾">
    <x-slot:actions>
        <a href="{{ route('portal.hq.purchase_orders.create') }}" class="inline-flex items-center gap-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm transition">＋ 구매발주 등록</a>
    </x-slot:actions>
</x-wms.page-head>

<form method="GET" class="flex flex-wrap items-center gap-2 mb-4">
    <select name="supplier" class="rounded-xl border-neutral-200 text-sm py-2">
        <option value="all">전체 공급처</option>
        @foreach ($suppliers as $s)<option value="{{ $s->id }}" @selected((string) $supplier === (string) $s->id)>{{ $s->name }}</option>@endforeach
    </select>
    <select name="status" class="rounded-xl border-neutral-200 text-sm py-2">
        <option value="all">전체 상태</option>
        @foreach (\App\Models\PurchaseOrder::STATUSES as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach
    </select>
    <input type="date" name="from" value="{{ $from }}" class="rounded-xl border-neutral-200 text-sm py-2">
    <span class="text-neutral-400">~</span>
    <input type="date" name="to" value="{{ $to }}" class="rounded-xl border-neutral-200 text-sm py-2">
    <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm">조회</button>
    @if ($supplier !== 'all' || $status !== 'all' || $from || $to)<a href="{{ url()->current() }}" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-500 font-bold px-3 py-2 text-sm">초기화</a>@endif
</form>

<x-wms.panel>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-5 py-3">발주번호</th>
                    <th class="text-left font-semibold px-5 py-3">공급처</th>
                    <th class="text-right font-semibold px-5 py-3">품목</th>
                    <th class="text-right font-semibold px-5 py-3">합계</th>
                    <th class="text-left font-semibold px-5 py-3">상태</th>
                    <th class="text-left font-semibold px-5 py-3 hidden md:table-cell">등록일</th>
                    <th class="text-right font-semibold px-5 py-3">관리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($orders as $o)
                    <tr class="hover:bg-mango-50/40">
                        <td class="px-5 py-3.5 font-bold text-mango-700 whitespace-nowrap">{{ $o->po_no }}</td>
                        <td class="px-5 py-3.5">{{ $o->supplier_name }}</td>
                        <td class="px-5 py-3.5 text-right text-neutral-500">{{ $o->items_count ?? $o->items()->count() }}건</td>
                        <td class="px-5 py-3.5 text-right font-black text-neutral-800 whitespace-nowrap">{{ number_format($o->total_amount) }}원</td>
                        <td class="px-5 py-3.5 whitespace-nowrap"><span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $chip[$o->status] ?? '' }}">{{ $o->status_label }}</span></td>
                        <td class="px-5 py-3.5 hidden md:table-cell text-neutral-500 whitespace-nowrap">{{ $o->created_at->format('Y-m-d') }}</td>
                        <td class="px-5 py-3.5 text-right whitespace-nowrap">
                            <a href="{{ route('portal.hq.purchase_orders.show', $o) }}" class="text-xs font-bold text-neutral-600 hover:text-mango-600 mr-3">상세</a>
                            @if ($o->statement_issued_at)
                                <a href="{{ route('portal.hq.purchase_orders.statement.pdf', $o) }}" target="_blank" class="text-xs font-bold text-mango-600 hover:text-mango-700">📄 거래명세서</a>
                            @else
                                <span class="text-xs text-neutral-400">명세서 미발행</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-neutral-400">구매발주가 없습니다.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-wms.panel>

<div class="mt-5">{{ $orders->links() }}</div>
@endsection
