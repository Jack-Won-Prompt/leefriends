@extends('portal.layout')
@section('title', '공급사 발주 현황')

@section('content')
<x-wms.page-head title="공급사 발주 현황" subtitle="매장 발주 중 공급처 직배송분(공급사별 판매주문)을 한눈에 확인합니다." icon="🏭" />

{{-- 요약 --}}
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
    <div class="rounded-2xl bg-gradient-to-br from-sky-500 to-sky-600 text-white p-6">
        <p class="text-white/80 font-semibold text-sm">공급사 공급액 합계</p>
        <p class="text-3xl font-black mt-1">{{ number_format($totalSupply) }}<span class="text-lg">원</span></p>
        <p class="text-white/70 text-xs mt-1">필터 기준 · 본사 매입</p>
    </div>
    <div class="rounded-2xl bg-white p-6 shadow-sm border border-neutral-100">
        <p class="text-sm text-neutral-500 font-medium">판매주문 건수</p>
        <p class="text-3xl font-black text-neutral-900 mt-1">{{ number_format($salesOrders->total()) }}<span class="text-lg">건</span></p>
    </div>
</div>

{{-- 필터 --}}
<form method="GET" class="flex flex-wrap items-center gap-2 mb-4">
    <select name="supplier" onchange="this.form.submit()" class="rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm py-2">
        <option value="all">전체 공급사</option>
        @foreach ($suppliers as $s)
            <option value="{{ $s->id }}" @selected((string) $supplierId === (string) $s->id)>{{ $s->name }}</option>
        @endforeach
    </select>
    <input type="hidden" name="status" value="{{ $status }}">
    <input type="date" name="from" value="{{ $from }}" class="rounded-xl border-neutral-200 text-sm py-2">
    <span class="text-neutral-400">~</span>
    <input type="date" name="to" value="{{ $to }}" class="rounded-xl border-neutral-200 text-sm py-2">
    <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm">조회</button>
    <div class="flex flex-wrap gap-1.5">
        <a href="{{ request()->fullUrlWithQuery(['status' => 'all']) }}"
           class="px-3.5 py-2 rounded-full text-sm font-bold {{ $status === 'all' ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 border border-neutral-200 hover:bg-mango-50' }}">전체</a>
        @foreach ($statuses as $key => $label)
            <a href="{{ request()->fullUrlWithQuery(['status' => $key]) }}"
               class="px-3.5 py-2 rounded-full text-sm font-bold {{ $status === $key ? 'bg-mango-500 text-white' : 'bg-white text-neutral-600 border border-neutral-200 hover:bg-mango-50' }}">{{ $label }}</a>
        @endforeach
    </div>
</form>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $salesOrders->map(fn ($so) => [
        'created_at' => $so->created_at->format('Y.m.d'),
        'sales_order_no' => $so->sales_order_no,
        'supplier_name' => optional($so->supplier)->name ?? '공급처',
        'store_name' => optional($so->store)->name ?? '-',
        'item_count' => (int) $so->item_count,
        'supply_amount' => (int) $so->supply_amount,
        'status' => $so->status,
        'status_label' => $so->status_label,
        'order_url' => $so->order ? route('portal.hq.orders.show', $so->order) : null,
    ])->values();
@endphp

<x-wms.panel>
    <div id="hqSupplierOrdersGrid"></div>
</x-wms.panel>

@if ($salesOrders->hasPages())
    <div class="mt-5">{{ $salesOrders->links() }}</div>
@endif

@push('scripts')
<script>
(function () {
    const ST_CLS = {
        created: 'bg-amber-100 text-amber-700',
        confirmed: 'bg-sky-100 text-sky-700',
        shipped: 'bg-indigo-100 text-indigo-700',
        received: 'bg-emerald-100 text-emerald-700',
        canceled: 'bg-neutral-100 text-neutral-400',
    };
    ww.grid('hqSupplierOrdersGrid', [
        { header: '발주일', name: 'created_at', width: 110 },
        { header: '판매주문', name: 'sales_order_no', width: 150 },
        { header: '공급사', name: 'supplier_name', width: 150 },
        { header: '매장', name: 'store_name', width: 130 },
        { header: '품목', name: 'item_count', width: 80, align: 'right', renderer: (v) => ww.num(v) + '건' },
        { header: '공급액', name: 'supply_amount', width: 130, align: 'right', renderer: (v) => ww.won(v) },
        { header: '상태', name: 'status', width: 100, align: 'center',
          renderer: (v, row) => ww.badge(row.status_label, ST_CLS[v] || 'bg-neutral-100 text-neutral-600') },
        { header: '상세', name: 'order_url', width: 80, align: 'right', sortable: false, exportable: false,
          renderer: (v) => {
              if (!v) return ww.dash();
              const a = document.createElement('a');
              a.href = v; a.textContent = '발주';
              a.className = 'text-xs font-bold text-mango-600 hover:text-mango-700';
              return a;
          } },
    ], @json($gridRows));
})();
</script>
@endpush
@endsection
