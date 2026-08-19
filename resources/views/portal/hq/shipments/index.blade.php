@extends('portal.layout')
@section('title', '출고 관리')

@section('content')
<x-wms.page-head title="출고 관리" icon="🚚" />

<x-wms.filter :action="route('portal.hq.shipments.index')" cols="grid-cols-2 md:grid-cols-3">
    <x-wms.field label="매장">
        <select name="store" class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <option value="all">전체 매장</option>
            @foreach ($stores as $s)
                <option value="{{ $s->id }}" @selected((string) $store === (string) $s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </x-wms.field>
    <x-wms.field label="발주일 기간">
        <div class="flex items-center gap-1.5">
            <input type="date" name="from" value="{{ $from }}" class="w-full min-w-0 rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            <span class="text-neutral-400 shrink-0">~</span>
            <input type="date" name="to" value="{{ $to }}" class="w-full min-w-0 rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
        </div>
    </x-wms.field>
</x-wms.filter>

<x-wms.toolbar :count="$orders->total()">
    <button type="button" id="btnPickingSlip"
            class="inline-flex items-center gap-1 rounded-lg bg-mango-500 hover:bg-mango-600 text-white px-4 py-1.5 text-xs font-bold transition">🖨️ 출고지시서 출력</button>
    <a href="{{ route('portal.hq.shipments.index') }}" class="inline-flex items-center gap-1 rounded-lg bg-white border border-neutral-200 px-3 py-1.5 text-xs font-bold text-neutral-500 hover:bg-neutral-100">새로고침</a>
</x-wms.toolbar>

@include('portal.partials.wwgrid-assets')
@php
    $gridRows = $orders->map(fn ($o) => [
        'id' => $o->id,
        'order_no' => $o->order_no,
        'store_name' => $o->store->name ?? '-',
        'items_count' => (int) $o->items_count,
        'store_amount' => (int) $o->store_amount,
        'status' => $o->status,
        'status_label' => $o->status_label,
        'has_pending' => (bool) $o->has_pending,
        'has_photo' => ! empty($o->delivery_photos),
        'has_sign' => (bool) $o->delivery_signature,
        'created_at' => $o->created_at->format('Y.m.d H:i'),
        'show_url' => route('portal.hq.orders.show', $o),
    ])->values();
@endphp

<x-wwgrid-tabs gid="hqShipGrid">
    <x-wms.panel>
        <div id="hqShipGrid"></div>
    </x-wms.panel>
    <div class="mt-5">{{ $orders->links() }}</div>
</x-wwgrid-tabs>

@push('scripts')
<script>
(function () {
    const PICK_URL = @json(route('portal.hq.shipments.picking'));
    const STATUS_CLS = {
        pending: 'bg-neutral-100 text-neutral-600', processing: 'bg-amber-100 text-amber-700',
        shipping: 'bg-sky-100 text-sky-700', completed: 'bg-emerald-100 text-emerald-700',
        canceled: 'bg-rose-100 text-rose-600',
    };
    const grid = ww.grid('hqShipGrid', [
        { header: '발주번호', name: 'order_no', width: 150 },
        { header: '매장', name: 'store_name', width: 150 },
        { header: '품목', name: 'items_count', width: 70, align: 'right' },
        { header: '출고가', name: 'store_amount', width: 120, align: 'right', renderer: (v) => ww.won(v) },
        { header: '상태', name: 'status', width: 100, align: 'center',
          renderer: (v, row) => ww.badge(row.status_label, STATUS_CLS[v] || STATUS_CLS.pending) },
        { header: '단가', name: 'has_pending', width: 100, align: 'center', sortable: false,
          renderer: (v) => v ? ww.badge('미확인', 'bg-rose-100 text-rose-600') : ww.badge('확정', 'bg-emerald-50 text-emerald-600') },
        { header: '배송증빙', name: 'has_photo', width: 120, align: 'center', sortable: false, exportable: false,
          renderer: (v, row) => {
              const wrap = ww.el('span', 'inline-flex gap-1 justify-center');
              wrap.appendChild(ww.badge('사진', row.has_photo ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-300'));
              wrap.appendChild(ww.badge('서명', row.has_sign ? 'bg-emerald-100 text-emerald-700' : 'bg-neutral-100 text-neutral-300'));
              return wrap;
          } },
        { header: '발주일', name: 'created_at', width: 150 },
    ], @json($gridRows), { rowCheckboxDisabled: (row) => row.status === 'canceled' });

    // 행 클릭 → 상세 탭(매장 발주 주문 상세와 동일 구성 재사용)
    ww.bindRowDetail('hqShipGrid', grid, 'show_url', 'order_no');

    document.getElementById('btnPickingSlip').addEventListener('click', function () {
        const rows = grid.getCheckedRows();
        if (!rows.length) { alert('출고지시서를 출력할 발주를 선택하세요.'); return; }
        const canceled = rows.filter((r) => r.status === 'canceled');
        if (canceled.length) {
            alert('취소된 발주가 포함되어 출고지시서를 출력할 수 없습니다.\n대상: ' + canceled.map((r) => r.order_no).join(', '));
            return;
        }
        const pend = rows.filter((r) => r.has_pending);
        if (pend.length) {
            alert('미확인 단가(싯가) 품목이 있는 발주가 포함되어 출고지시서를 출력할 수 없습니다.\n대상: ' + pend.map((r) => r.order_no).join(', ') + '\n단가 확정 후 다시 시도하세요.');
            return;
        }
        const qs = rows.map((r) => 'orders[]=' + encodeURIComponent(r.id)).join('&');
        window.open(PICK_URL + '?' + qs, '_blank');
    });
})();
</script>
@endpush
@endsection
