@extends('portal.layout')
@section('title', '매장 발주 주문')

@section('content')
<x-wms.page-head title="매장 발주 주문" subtitle="매장이 접수한 구매주문을 조회합니다" icon="📦" />

<x-wms.filter :action="route('portal.hq.orders.index')" cols="grid-cols-2 md:grid-cols-4">
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
    <x-wms.field label="세금계산서">
        <select name="tax" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="all" @selected($tax === 'all')>전체</option>
            <option value="issued" @selected($tax === 'issued')>발행완료</option>
            <option value="pending" @selected($tax === 'pending')>미발행</option>
        </select>
    </x-wms.field>
    <x-wms.field label="접수일 기간">
        <div class="flex items-center gap-1.5">
            <input type="date" name="from" value="{{ $from }}" class="w-full min-w-0 rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <span class="text-neutral-400 shrink-0">~</span>
            <input type="date" name="to" value="{{ $to }}" class="w-full min-w-0 rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
        </div>
    </x-wms.field>
</x-wms.filter>

<x-wms.toolbar :count="$orders->total()">
    <a href="{{ route('portal.hq.orders.index') }}" class="inline-flex items-center gap-1 rounded-lg bg-white border border-neutral-200 px-3 py-1.5 text-xs font-bold text-neutral-500 hover:bg-neutral-100">새로고침</a>
</x-wms.toolbar>

<x-wms.panel>
    @if ($orders->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">발주 내역이 없습니다.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-3">주문번호</th>
                        <th class="text-left font-semibold px-6 py-3">매장</th>
                        <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">품목</th>
                        <th class="text-right font-semibold px-6 py-3">출고가</th>
                        <th class="text-right font-semibold px-6 py-3 hidden lg:table-cell">공급가(원가)</th>
                        <th class="text-left font-semibold px-6 py-3">상태</th>
                        <th class="text-left font-semibold px-6 py-3">세금계산서</th>
                        <th class="text-left font-semibold px-6 py-3">입금요청</th>
                        <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">접수일</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($orders as $o)
                        <tr class="hover:bg-mango-50/40 transition cursor-pointer" onclick="location.href='{{ route('portal.hq.orders.show', $o) }}'">
                            <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $o->order_no }}</td>
                            <td class="px-6 py-3.5">{{ $o->store->name ?? '-' }}</td>
                            <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ $o->items_count }}</td>
                            <td class="px-6 py-3.5 text-right font-semibold">{{ number_format($o->store_amount) }}원</td>
                            <td class="px-6 py-3.5 text-right hidden lg:table-cell text-neutral-500">{{ number_format($o->supply_amount) }}원</td>
                            <td class="px-6 py-3.5">@include('portal.partials.order-status', ['status' => $o->status, 'label' => $o->status_label])</td>
                            <td class="px-6 py-3.5">
                                @if ($o->tax_invoice_id)
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">발행완료</span>
                                @else
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-neutral-100 text-neutral-400">미발행</span>
                                @endif
                            </td>
                            <td class="px-6 py-3.5" onclick="event.stopPropagation()">
                                @if ($o->status === 'canceled')
                                    <span class="text-xs text-neutral-300">—</span>
                                @elseif ($o->paid_at)
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">입금완료</span>
                                @else
                                    <form method="POST" action="{{ route('portal.hq.orders.payment_request', $o) }}"
                                          onsubmit="return confirm('{{ $o->store->name ?? '매장' }}({{ $o->store->phone ?? '번호없음' }})에 입금요청 SMS를 전송합니다.\n발주금액 {{ number_format($o->order_total) }}원\n진행하시겠습니까?')">
                                        @csrf
                                        <button type="submit" @unless ($o->store?->phone) disabled @endunless
                                                class="inline-flex items-center gap-1 rounded-lg bg-mango-500 hover:bg-mango-600 disabled:opacity-40 text-white font-bold px-3 py-1.5 text-xs transition">💬 입금요청</button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 hidden md:table-cell text-neutral-400">{{ $o->created_at->format('Y.m.d H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-wms.panel>

<div class="mt-5">{{ $orders->links() }}</div>
@endsection
