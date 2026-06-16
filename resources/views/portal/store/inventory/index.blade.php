@extends('portal.layout')
@section('title', '재고 관리')

@section('content')
<div x-data="{ useModal: false, invId: null, invName: '', invUnit: '', max: 0 }">

<x-wms.page-head title="재고 관리" subtitle="현재 재고 조회 및 바코드 사용 출고" icon="📦">
    <x-slot:actions>
        <a href="{{ route('portal.store.inventory.movements') }}" class="inline-flex items-center gap-1 rounded-xl bg-white border border-neutral-200 px-4 py-2 text-sm font-bold text-neutral-500 hover:bg-neutral-100">📜 입출고 내역</a>
    </x-slot:actions>
</x-wms.page-head>

{{-- 바코드 출고 (스캔/수기) --}}
<div class="rounded-2xl bg-neutral-900 text-white p-6 mb-6">
    <h3 class="font-extrabold mb-1">📷 바코드 재고 출고</h3>
    <p class="text-white/60 text-sm mb-4">제품 바코드를 스캔(또는 입력)하고 사용 수량을 입력하면 재고가 차감됩니다. (모바일 앱 스캔 연동)</p>
    <form method="POST" action="{{ route('portal.store.inventory.usage') }}" class="flex flex-col sm:flex-row gap-3">
        @csrf
        <input type="text" name="barcode" required autofocus placeholder="바코드 스캔/입력 (예: LF00000001)"
               class="flex-1 rounded-xl border-0 text-neutral-900 focus:ring-2 focus:ring-mango-400">
        <input type="number" name="qty" required min="1" value="1" placeholder="수량"
               class="w-full sm:w-28 rounded-xl border-0 text-neutral-900 focus:ring-2 focus:ring-mango-400">
        <button class="rounded-xl bg-mango-500 hover:bg-mango-600 font-bold px-6 py-3 transition">출고</button>
    </form>
</div>

{{-- 검색 --}}
<form method="GET" class="mb-5 flex gap-2">
    <input type="text" name="q" value="{{ $keyword }}" placeholder="품목명 검색"
           class="rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 w-full sm:w-72">
    <button class="rounded-xl bg-neutral-900 text-white font-bold px-5">검색</button>
</form>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">현재 재고</div>
    @if ($inventories->isEmpty())
        <p class="px-6 py-16 text-center text-neutral-400">보유 재고가 없습니다. 입고가 완료되면 표시됩니다.</p>
    @else
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">품목</th>
                    <th class="text-left font-semibold px-6 py-3">단위</th>
                    <th class="text-right font-semibold px-6 py-3">현재 수량</th>
                    <th class="text-right font-semibold px-6 py-3 w-28">출고</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($inventories as $inv)
                    <tr class="hover:bg-mango-50/40 transition {{ $inv->qty <= 0 ? 'opacity-50' : '' }}">
                        <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $inv->product_name }}</td>
                        <td class="px-6 py-3.5 text-neutral-500">{{ $inv->unit_name }}</td>
                        <td class="px-6 py-3.5 text-right font-black {{ $inv->qty <= 0 ? 'text-neutral-400' : 'text-neutral-900' }}">{{ number_format($inv->qty) }}</td>
                        <td class="px-6 py-3.5 text-right">
                            <button type="button" @click="useModal=true; invId={{ $inv->id }}; invName='{{ $inv->product_name }}'; invUnit='{{ $inv->unit_name }}'; max={{ $inv->qty }}"
                                    class="rounded-lg bg-neutral-100 hover:bg-neutral-200 px-3 py-1.5 font-semibold" {{ $inv->qty <= 0 ? 'disabled' : '' }}>출고</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- 출고 모달 --}}
<div x-show="useModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/50" @click="useModal=false"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <h3 class="text-lg font-extrabold text-neutral-900 mb-1">재고 출고</h3>
        <p class="text-sm text-neutral-500 mb-4"><span x-text="invName"></span> (보유 <span x-text="max"></span><span x-text="invUnit"></span>)</p>
        <form method="POST" action="{{ route('portal.store.inventory.usage') }}" class="space-y-3">
            @csrf
            <input type="hidden" name="inventory_id" :value="invId">
            <div>
                <label class="block text-sm font-bold text-neutral-700 mb-1.5">사용 수량</label>
                <input type="number" name="qty" required min="1" :max="max" value="1"
                       class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400">
            </div>
            <input type="text" name="note" placeholder="메모(선택)" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <div class="flex gap-2 pt-1">
                <button class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold py-3 transition">출고 처리</button>
                <button type="button" @click="useModal=false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 font-bold px-5 py-3">취소</button>
            </div>
        </form>
    </div>
</div>

</div>
@endsection
