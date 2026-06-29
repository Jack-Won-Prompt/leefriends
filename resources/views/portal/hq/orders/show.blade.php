@extends('portal.layout')
@section('title', '발주 상세')

@section('content')
<a href="{{ route('portal.hq.orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600 mb-5">← 발주 관리</a>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 rounded-2xl bg-white shadow-sm border border-neutral-100 p-7">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black text-neutral-900">{{ $order->order_no }}</h2>
                <p class="text-sm text-neutral-400 mt-1">{{ $order->created_at->format('Y년 m월 d일 H:i') }}</p>
            </div>
            @include('portal.partials.order-status', ['status' => $order->status, 'label' => $order->status_label])
        </div>
        <dl class="grid sm:grid-cols-2 gap-x-8 gap-y-3 mt-6 text-sm">
            <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">발주 매장</dt><dd class="font-bold">{{ $order->store->name ?? '-' }}</dd></div>
            <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">발주자</dt><dd>{{ $order->user->name ?? '-' }}</dd></div>
            <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">배송지</dt><dd class="text-right">{{ $order->store ? ($order->store->postcode ? '('.$order->store->postcode.') ' : '').$order->store->full_delivery_address : '-' }}</dd></div>
            <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">연락처</dt><dd>{{ $order->store->phone ?? '-' }}</dd></div>
        </dl>
        @if ($order->note)
            <p class="mt-4 text-sm text-neutral-600 bg-neutral-50 rounded-xl p-4">📝 {{ $order->note }}</p>
        @endif
    </div>

    <div class="rounded-2xl bg-neutral-900 text-white p-7"
         x-data="{ shipOpen: false, stmtOpen: false, box: {{ (int) ($order->shipping_box_count ?? 0) }}, unit: {{ (int) ($order->shipping_unit_price ?? 0) }}, get fee() { return (this.box || 0) * (this.unit || 0); } }">
        <h3 class="font-bold text-white/70 text-sm mb-4">정산 요약</h3>
        <div class="flex justify-between py-2 border-b border-white/10"><span class="text-white/70">매장 출고가 합계</span><span class="font-bold">{{ number_format($order->store_amount) }}원</span></div>
        <div class="flex justify-between py-2 border-b border-white/10">
            <span class="text-white/70">택배비 합계
                @if ($order->shipping_fee)<span class="text-white/40 text-xs">({{ number_format($order->shipping_box_count) }}박스 × {{ number_format($order->shipping_unit_price) }}원)</span>@endif
            </span>
            <span class="font-bold">{{ number_format($order->shipping_fee) }}원</span>
        </div>
        <div class="flex justify-between py-3 mt-1"><span class="text-mango-300 font-bold">발주 합계</span><span class="text-mango-300 font-black text-lg">{{ number_format($order->order_total) }}원</span></div>

        <button type="button" @click="shipOpen = true"
                class="w-full mt-2 rounded-xl bg-white/10 hover:bg-white/15 text-white/90 font-bold px-4 py-2.5 text-sm transition">
            🚚 택배비 {{ $order->shipping_fee ? '수정' : '추가' }}
        </button>

        {{-- 택배비 추가/수정 오버레이 팝업 --}}
        <div x-show="shipOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             @keydown.escape.window="shipOpen = false">
            <div class="bg-white text-neutral-800 rounded-2xl shadow-xl w-full max-w-sm p-6" @click.outside="shipOpen = false">
                <h3 class="text-lg font-extrabold text-neutral-900 mb-4">🚚 택배비 입력</h3>
                <form method="POST" action="{{ route('portal.hq.orders.shipping', $order) }}" class="space-y-3">
                    @csrf @method('PATCH')
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-1.5">박스 수</label>
                        <input type="number" name="shipping_box_count" x-model.number="box" min="0" max="9999"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="0">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-1.5">박스당 단가 (원)</label>
                        <input type="number" name="shipping_unit_price" x-model.number="unit" min="0" max="9999999"
                               class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm" placeholder="0">
                    </div>
                    <div class="flex justify-between items-end rounded-xl bg-neutral-50 px-4 py-3">
                        <span class="text-sm font-semibold text-neutral-600">택배비 합계</span>
                        <span class="text-xl font-black text-mango-700"><span x-text="fee.toLocaleString()"></span>원</span>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition">저장</button>
                        <button type="button" @click="shipOpen = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 거래명세서: PDF 모달 / 이메일 전송 / 전송상태 --}}
        <div class="mt-5 pt-4 border-t border-white/10">
            <p class="text-white/50 text-xs mb-2 font-semibold">거래명세서</p>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" @click="stmtOpen = true"
                        class="rounded-xl bg-white/10 hover:bg-white/15 text-white/90 font-bold py-2.5 text-sm transition">🧾 거래명세서</button>
                <form method="POST" action="{{ route('portal.hq.orders.statement.email', $order) }}"
                      onsubmit="return confirm('거래명세서 PDF를 매장({{ $order->store->email }})으로 전송합니다.\n진행하시겠습니까?')">
                    @csrf
                    <button type="submit" @if (! $order->store?->email) disabled @endif
                            class="w-full rounded-xl bg-sky-500 hover:bg-sky-600 disabled:opacity-40 text-white font-bold py-2.5 text-sm transition">📧 {{ $order->statement_emailed_at ? '재전송' : '이메일 보내기' }}</button>
                </form>
            </div>
            @if ($order->statement_emailed_at)
                <p class="text-emerald-300 text-xs mt-2">✓ {{ $order->statement_emailed_at->format('Y.m.d H:i') }} 매장 전송됨@if ($order->statement_email_count > 1) ({{ $order->statement_email_count }}회)@endif</p>
            @else
                <p class="text-white/40 text-xs mt-2">미전송@unless ($order->store?->email) · 매장 이메일 없음@endunless</p>
            @endif

            {{-- 거래명세서 PDF 모달 팝업 --}}
            <div x-show="stmtOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4" @keydown.escape.window="stmtOpen = false">
                <div class="relative mx-auto max-w-3xl my-8 text-neutral-800">
                    <div class="flex items-center justify-end gap-2 mb-3">
                        <a href="{{ route('portal.hq.orders.statement.pdf', $order) }}" target="_blank"
                           class="rounded-xl bg-white/90 hover:bg-white text-neutral-700 font-bold px-4 py-2 text-sm shadow">⬇ PDF 다운로드</a>
                        <button type="button" @click="stmtOpen = false" class="rounded-xl bg-white/90 hover:bg-white text-neutral-700 font-bold px-4 py-2 text-sm shadow">닫기 ✕</button>
                    </div>
                    @include('portal.partials.store-order-statement-document', ['order' => $order])
                </div>
            </div>
        </div>

        @php($taxInvoice = $order->taxInvoice)
        <div class="mt-5 pt-4 border-t border-white/10">
            @if ($taxInvoice)
                <div class="rounded-xl bg-emerald-500/15 border border-emerald-400/30 px-4 py-3 text-sm">
                    <p class="font-bold text-emerald-300">✓ 세금계산서 발행 완료</p>
                    <p class="text-white/70 mt-1">계산서번호 {{ $taxInvoice->invoice_no }} · 합계 {{ number_format($taxInvoice->total_amount) }}원</p>
                    <p class="text-white/50 text-xs mt-0.5">{{ $taxInvoice->invoicee_corp_name }} ({{ $taxInvoice->invoicee_email }})</p>
                    @if ($taxInvoice->status === 'issued')
                        <form method="POST" action="{{ route('portal.hq.tax_invoices.cancel', $taxInvoice) }}" class="mt-3"
                              onsubmit="return confirm('이 세금계산서를 발행취소합니다. 진행하시겠습니까?\n(국세청 전송 완료 후에는 취소되지 않을 수 있습니다.)')">
                            @csrf
                            <button type="submit" class="text-xs font-bold text-rose-300 hover:text-rose-200 underline">발행취소</button>
                        </form>
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('portal.hq.tax_invoices.issue', $order) }}"
                      onsubmit="return confirm('본사 → 매장 세금계산서를 발행합니다.\n수신: {{ $order->store->name }} ({{ $order->store->email }})\n진행하시겠습니까?')">
                    @csrf
                    <button type="submit"
                            class="w-full rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-3 text-sm transition">
                        🧾 세금계산서 발행 (본사 → 매장)
                    </button>
                    @unless ($order->store?->biz_no)
                        <p class="text-amber-300/80 text-xs mt-2">⚠ 매장 사업자등록번호가 없습니다. 매장 관리에서 먼저 등록하세요.</p>
                    @endunless
                </form>
            @endif
        </div>
    </div>
</div>

{{-- 품목: 공급처명 확인 가능 --}}
<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden"
     x-data="{ itemOpen: false, f: { id: null, name: '', supply: 0, store: 0, qty: 1, unit: '', isSupplier: false },
               get lineStore() { return (this.store || 0) * (this.qty || 0); },
               openItem(d) { this.f = d; this.itemOpen = true; } }">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900 flex items-center justify-between">
        <span>발주 품목 · 공급처 / 배송현황</span>
        <span class="text-xs font-semibold text-neutral-400">품목명을 클릭해 수정</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-neutral-50 text-neutral-500">
                <tr>
                    <th class="text-left font-semibold px-6 py-3">품목</th>
                    <th class="text-left font-semibold px-6 py-3">공급 구분 / 공급처</th>
                    <th class="text-right font-semibold px-6 py-3">공급가</th>
                    <th class="text-right font-semibold px-6 py-3">출고가</th>
                    <th class="text-right font-semibold px-6 py-3">수량</th>
                    <th class="text-right font-semibold px-6 py-3 hidden md:table-cell">공급액</th>
                    <th class="text-left font-semibold px-6 py-3">배송</th>
                    <th class="text-right font-semibold px-6 py-3 w-44">본사 직공급 처리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @foreach ($order->items as $it)
                    <tr>
                        <td class="px-6 py-3.5 font-bold">
                            <button type="button"
                                    @click="openItem({ id: {{ $it->id }}, name: {{ Illuminate\Support\Js::from($it->product_name) }}, supply: {{ (int) $it->supply_unit_price }}, store: {{ (int) $it->store_unit_price }}, qty: {{ (int) $it->qty }}, unit: {{ Illuminate\Support\Js::from($it->unit) }}, isSupplier: {{ $it->supply_type === 'supplier' ? 'true' : 'false' }} })"
                                    class="text-left text-neutral-900 hover:text-mango-600 hover:underline">{{ $it->product_name }}</button>
                        </td>
                        <td class="px-6 py-3.5">
                            @if ($it->supply_type === 'supplier')
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-sky-100 text-sky-700">공급처</span>
                                <span class="font-semibold text-neutral-800 ml-1">{{ $it->supplier_name }}</span>
                            @else
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-mango-100 text-mango-700">본사 직공급</span>
                            @endif
                        </td>
                        <td class="px-6 py-3.5 text-right text-neutral-500">{{ $it->supply_type === 'supplier' ? number_format($it->supply_unit_price).'원' : '-' }}</td>
                        <td class="px-6 py-3.5 text-right">@if ($it->price_pending)<span class="text-amber-600 font-bold">싯가</span>@else{{ number_format($it->store_unit_price) }}원@endif</td>
                        <td class="px-6 py-3.5 text-right">{{ number_format($it->qty) }}{{ $it->unit }}</td>
                        <td class="px-6 py-3.5 text-right hidden md:table-cell text-neutral-500">{{ $it->supply_type === 'supplier' ? number_format($it->supply_line_amount).'원' : '-' }}</td>
                        <td class="px-6 py-3.5">@include('portal.partials.fulfillment-status', ['status' => $it->fulfillment_status, 'label' => $it->fulfillment_label])</td>
                        <td class="px-6 py-3.5">
                            @if ($it->price_pending)
                                {{-- 싯가 단가 확정 --}}
                                <form method="POST" action="{{ route('portal.hq.orders.items.price', [$order, $it]) }}"
                                      class="flex justify-end items-center gap-1.5">
                                    @csrf @method('PATCH')
                                    <input type="number" name="store_unit_price" min="1" required placeholder="단가"
                                           class="w-24 rounded-lg border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm py-1.5 text-right">
                                    <button class="rounded-lg px-3 py-1.5 font-semibold text-xs bg-mango-500 text-white hover:bg-mango-600 whitespace-nowrap">단가 확정</button>
                                </form>
                            @elseif ($it->supply_type === 'hq')
                                <div class="flex justify-end gap-1.5">
                                    @foreach (['shipping' => '배송중', 'delivered' => '완료'] as $st => $lbl)
                                        @if ($it->fulfillment_status !== $st)
                                            <form method="POST" action="{{ route('portal.hq.orders.items.update', [$order, $it]) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="fulfillment_status" value="{{ $st }}">
                                                <button class="rounded-lg px-3 py-1.5 font-semibold text-xs {{ $st === 'delivered' ? 'bg-emerald-500 text-white hover:bg-emerald-600' : 'bg-sky-100 text-sky-700 hover:bg-sky-200' }}">{{ $lbl }}</button>
                                            </form>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <span class="block text-right text-xs text-neutral-400">공급처 직배송</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- 품목 수정 팝업 (공급가·출고가·수량) --}}
    <div x-show="itemOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
         @keydown.escape.window="itemOpen = false">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6" @click.outside="itemOpen = false">
            <h3 class="text-lg font-extrabold text-neutral-900 mb-1">품목 수정</h3>
            <p class="text-sm text-neutral-500 mb-4" x-text="f.name"></p>
            <form method="POST" :action="'{{ url('portal/hq/orders/'.$order->id.'/items') }}/' + f.id + '/edit'" class="space-y-3">
                @csrf @method('PATCH')
                <div x-show="f.isSupplier">
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">공급가 (원) <span class="text-neutral-400 font-normal">공급처 단가</span></label>
                    <input type="number" name="supply_unit_price" x-model.number="f.supply" min="0"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm text-right">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">출고가 (원) <span class="text-neutral-400 font-normal">매장 판매가</span></label>
                    <input type="number" name="store_unit_price" x-model.number="f.store" min="0" required
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm text-right">
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">수량 <span class="text-neutral-400 font-normal" x-text="f.unit ? '('+f.unit+')' : ''"></span></label>
                    <input type="number" name="qty" x-model.number="f.qty" min="1" max="99999" required
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm text-right">
                </div>
                <div class="flex justify-between items-end rounded-xl bg-neutral-50 px-4 py-3">
                    <span class="text-sm font-semibold text-neutral-600">출고 금액 (출고가 × 수량)</span>
                    <span class="text-lg font-black text-mango-700"><span x-text="lineStore.toLocaleString()"></span>원</span>
                </div>
                <p class="text-[11px] text-neutral-400">수정 내용은 매장 발주 내역과 판매주문·정산에 즉시 반영됩니다.</p>
                <div class="flex gap-2 pt-1">
                    <button type="submit" class="flex-1 rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-4 py-2.5 text-sm transition">저장</button>
                    <button type="button" @click="itemOpen = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-600 font-bold px-4 py-2.5 text-sm">취소</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
