@extends('portal.layout')
@php $isSample = $order->isSample(); @endphp
@section('title', $isSample ? '샘플 주문 상세' : '발주 상세')

@section('content')
<div x-data="{ open: null }">
@if ($isSample)
    <a href="{{ route('portal.store.sample_orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-violet-600 mb-2">← 샘플 주문 내역</a>
@else
    <a href="{{ route('portal.store.orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600 mb-2">← 발주 내역</a>
@endif

{{-- 하나의 카드: 헤더 + 액션 한 줄 --}}
<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-4 mb-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2 flex-wrap">
            <h2 class="text-xl font-black text-neutral-900">{{ $order->order_no }}</h2>
            @if ($isSample)<span class="text-xs font-bold px-2.5 py-1 rounded-full bg-violet-100 text-violet-700">샘플 · 무상</span>@endif
            <span class="text-xs text-neutral-400">{{ $isSample ? '주문일' : '발주일' }} {{ $order->created_at->format('Y년 m월 d일 H:i') }}</span>
            @include('portal.partials.order-status', ['status' => $order->status, 'label' => $order->status_label])
            @include('portal.partials.payment-badge', ['order' => $order])
        </div>
        <div class="flex flex-wrap items-center gap-1.5">
            @unless ($isSample)
                <button type="button" @click="open = {{ $order->id }}"
                        class="rounded-lg bg-neutral-900 hover:bg-mango-600 text-white font-bold px-3 py-1.5 text-xs transition">🧾 거래명세서</button>
            @endunless
            @if (! empty($editable))
                <a href="{{ route('portal.store.orders.edit', $order) }}" class="rounded-lg bg-neutral-100 hover:bg-neutral-200 text-neutral-700 font-bold px-3 py-1.5 text-xs transition">✏️ 수정</a>
                <form method="POST" action="{{ route('portal.store.orders.destroy', $order) }}" class="inline" onsubmit="return confirm('이 발주를 취소할까요? 본사·공급처에 취소 알림이 전송됩니다.')">
                    @csrf @method('DELETE')
                    <button class="rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold px-3 py-1.5 text-xs transition">발주 취소</button>
                </form>
            @endif
        </div>
    </div>
    @if ($order->note)
        <p class="mt-2 text-sm text-neutral-600 bg-neutral-50 rounded-xl px-3 py-2">📝 {{ $order->note }}</p>
    @endif
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">발주 품목 · 배송현황</div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">품목</th>
                    <th class="text-left font-semibold px-6 py-3 hidden md:table-cell">공급</th>
                    @unless ($isSample)<th class="text-right font-semibold px-6 py-3">단가</th>@endunless
                    <th class="text-right font-semibold px-6 py-3">수량</th>
                    @unless ($isSample)<th class="text-right font-semibold px-6 py-3">금액</th>@endunless
                    <th class="text-left font-semibold px-6 py-3">배송</th>
                    @if (! empty($editable))<th class="text-center font-semibold px-6 py-3 w-16">관리</th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($order->items as $it)
                    <tr>
                        <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $it->product_name }}</td>
                        <td class="px-6 py-3.5 hidden md:table-cell">
                            @if ($it->supply_type === 'supplier')
                                <span class="text-xs font-bold text-sky-700">{{ $it->supplier_name }} (직배송)</span>
                            @else
                                <span class="text-xs font-bold text-mango-700">본사</span>
                            @endif
                        </td>
                        @unless ($isSample)<td class="px-6 py-3.5 text-right">@if ($it->price_pending)<span class="text-amber-600 font-semibold">싯가</span>@else{{ number_format($it->store_unit_price) }}원@endif</td>@endunless
                        <td class="px-6 py-3.5 text-right">{{ number_format($it->qty) }}{{ $it->unit }}</td>
                        @unless ($isSample)<td class="px-6 py-3.5 text-right font-semibold">@if ($it->price_pending)<span class="text-amber-600">확정 대기</span>@else{{ number_format($it->store_line_amount) }}원@endif</td>@endunless
                        <td class="px-6 py-3.5">@include('portal.partials.fulfillment-status', ['status' => $it->fulfillment_status, 'label' => $it->fulfillment_label])</td>
                        @if (! empty($editable))
                            <td class="px-6 py-3.5 text-center">
                                <form method="POST" action="{{ route('portal.store.orders.items.destroy', [$order, $it]) }}"
                                      onsubmit="return confirm('«{{ $it->product_name }}» 품목을 발주에서 삭제할까요? 본사·공급처에 변경 알림이 전송됩니다.')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-rose-200 text-rose-600 hover:bg-rose-50 px-2.5 py-1.5 text-xs font-bold" title="품목 삭제">🗑 삭제</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
            @unless ($isSample)
            <tfoot>
                <tr class="bg-neutral-50 font-black">
                    <td class="px-6 py-4" colspan="4">합계</td>
                    <td class="px-6 py-4 text-right text-mango-700 text-lg">{{ number_format($order->store_amount) }}원</td>
                    <td></td>
                    @if (! empty($editable))<td></td>@endif
                </tr>
            </tfoot>
            @endunless
        </table>
    </div>

    @unless ($isSample)
        {{-- 거래명세서 모달 팝업 --}}
        <x-detail-modal :id="$order->id">
            <x-slot:actions>
                <button type="button" onclick="printStatement('{{ route('portal.store.orders.statement', ['order' => $order, 'print' => 1]) }}')"
                        class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-4 py-2 text-sm shadow">🖨️ 인쇄</button>
            </x-slot:actions>
            @include('portal.partials.store-order-statement-document', ['order' => $order])
        </x-detail-modal>
    @endunless
</div>

@push('scripts')
<script>
    // 새 탭 없이 페이지 내 숨김 iframe으로 거래명세서를 인쇄 (?print=1 → 로드 후 자동 인쇄)
    function printStatement(url) {
        document.querySelectorAll('iframe.__print-frame').forEach(el => el.remove());
        const f = document.createElement('iframe');
        f.className = '__print-frame';
        f.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
        f.src = url;
        f.onload = () => {
            try {
                f.contentWindow.addEventListener('afterprint', () => setTimeout(() => f.remove(), 300));
            } catch (e) {}
            setTimeout(() => { if (document.body.contains(f)) f.remove(); }, 120000);
        };
        document.body.appendChild(f);
    }
</script>
@endpush
@endsection
