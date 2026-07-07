@extends('portal.layout')
@section('title', '본사 구매발주')

@section('content')
@php $chip = ['ordered'=>'bg-sky-100 text-sky-700','confirmed'=>'bg-amber-100 text-amber-700','received'=>'bg-emerald-100 text-emerald-700','canceled'=>'bg-neutral-100 text-neutral-400']; @endphp
<div x-data="{ open: null }">
<x-wms.page-head title="본사 구매발주" subtitle="본사가 우리 공급처에 등록한 매입 발주입니다. 발주번호를 클릭하면 상세가 열립니다." icon="🧾">
    <x-slot:actions>
        <form method="GET">
            <select name="status" onchange="this.form.submit()" class="rounded-xl border-neutral-200 text-sm py-2">
                <option value="all">전체 상태</option>
                @foreach (\App\Models\PurchaseOrder::STATUSES as $k => $v)<option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>@endforeach
            </select>
        </form>
    </x-slot:actions>
</x-wms.page-head>

<x-wms.panel>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-5 py-3">발주번호</th>
                    <th class="text-right font-semibold px-5 py-3">품목</th>
                    <th class="text-right font-semibold px-5 py-3">합계</th>
                    <th class="text-left font-semibold px-5 py-3">상태</th>
                    <th class="text-left font-semibold px-5 py-3 hidden md:table-cell">등록일</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($orders as $o)
                    <tr class="hover:bg-mango-50/40">
                        <td class="px-5 py-3.5 whitespace-nowrap">
                            <button type="button" @click="open = {{ $o->id }}" class="font-bold text-mango-700 hover:underline">{{ $o->po_no }}</button>
                        </td>
                        <td class="px-5 py-3.5 text-right text-neutral-500">{{ $o->items->count() }}건</td>
                        <td class="px-5 py-3.5 text-right font-black text-neutral-800 whitespace-nowrap">{{ number_format($o->total_amount) }}원</td>
                        <td class="px-5 py-3.5 whitespace-nowrap"><span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $chip[$o->status] ?? '' }}">{{ $o->status_label }}</span></td>
                        <td class="px-5 py-3.5 hidden md:table-cell text-neutral-500 whitespace-nowrap">{{ $o->created_at->format('Y-m-d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-neutral-400">수신한 구매발주가 없습니다.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-wms.panel>

<div class="mt-5">{{ $orders->links() }}</div>

{{-- 상세 모달 --}}
@foreach ($orders as $o)
    <x-detail-modal :id="$o->id">
        @if ($o->status === 'ordered')
            <x-slot:actions>
                <form method="POST" action="{{ route('portal.supplier.purchase_orders.confirm', $o) }}" onsubmit="return confirm('이 구매발주를 확인할까요?')">
                    @csrf
                    <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm shadow">✅ 발주 확인</button>
                </form>
            </x-slot:actions>
        @endif
        <div class="bg-white rounded-2xl shadow border border-neutral-200 overflow-hidden">
            <div class="bg-mango-500 text-white px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black">구매발주 {{ $o->po_no }}</h2>
                    <p class="text-white/80 text-sm">본사 → {{ $o->supplier_name }} · {{ $o->created_at->format('Y-m-d H:i') }}</p>
                </div>
                <span class="text-xs font-bold px-3 py-1.5 rounded-full bg-white/20">{{ $o->status_label }}</span>
            </div>
            @if ($o->note)<div class="px-6 py-3 border-b border-neutral-100 text-sm text-neutral-500">메모: <b class="text-neutral-800">{{ $o->note }}</b></div>@endif
            <table class="w-full text-sm">
                <thead class="bg-neutral-50 text-neutral-500">
                    <tr>
                        <th class="text-left font-semibold px-6 py-2.5">품목</th>
                        <th class="text-left font-semibold px-6 py-2.5">단위</th>
                        <th class="text-right font-semibold px-6 py-2.5">단가</th>
                        <th class="text-right font-semibold px-6 py-2.5">수량</th>
                        <th class="text-right font-semibold px-6 py-2.5">금액</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach ($o->items as $it)
                        <tr>
                            <td class="px-6 py-2.5 font-semibold text-neutral-800">{{ $it->product_name }}</td>
                            <td class="px-6 py-2.5 text-neutral-500">{{ $it->unit }}</td>
                            <td class="px-6 py-2.5 text-right">{{ number_format($it->unit_price) }}원</td>
                            <td class="px-6 py-2.5 text-right">{{ number_format($it->qty) }}</td>
                            <td class="px-6 py-2.5 text-right font-bold">{{ number_format($it->line_amount) }}원</td>
                        </tr>
                    @endforeach
                    <tr class="bg-neutral-50 font-black">
                        <td class="px-6 py-3" colspan="4">합계</td>
                        <td class="px-6 py-3 text-right text-mango-700">{{ number_format($o->total_amount) }}원</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-detail-modal>
@endforeach
</div>
@endsection
