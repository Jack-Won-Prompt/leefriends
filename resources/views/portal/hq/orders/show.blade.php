@extends('portal.layout')
@section('title', '발주 상세')

@section('content')
<a href="{{ route('portal.hq.orders.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600 mb-2">← 발주 관리</a>

@php $shipAddr = $order->store ? ($order->store->postcode ? '('.$order->store->postcode.') ' : '').$order->store->full_delivery_address : '-'; @endphp
@php($taxInvoice = $order->taxInvoice)
@php($hasPending = $order->hasPendingPrice())
@php($pendingAlert = "alert('미확인 단가(싯가) 품목이 있어 거래명세서를 출력할 수 없습니다.\\n단가 확정 후 다시 시도하세요.')")
<div x-data="{ shipOpen: false, stmtOpen: false, box: {{ (int) ($order->shipping_box_count ?? 0) }}, unit: {{ (int) ($order->shipping_unit_price ?? 0) }}, stmtDate: '{{ $order->created_at->format('Y-m-d') }}', get fee() { return (this.box || 0) * (this.unit || 0); }, get stmtDateLabel() { const p = (this.stmtDate || '').split('-'); return p.length === 3 ? `${p[0]}년 ${p[1]}월 ${p[2]}일` : this.stmtDate; } }">
    {{-- 하나의 카드: 헤더+액션 / 발주 정보 한 줄 / 정산 요약 한 줄 --}}
    <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-4 mb-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2 flex-wrap">
                <h2 class="text-xl font-black text-neutral-900">{{ $order->order_no }}</h2>
                <span class="text-xs text-neutral-400">{{ $order->created_at->format('Y.m.d H:i') }}</span>
                @include('portal.partials.order-status', ['status' => $order->status, 'label' => $order->status_label])
            </div>
            @unless ($order->status === 'canceled')
                <div class="flex flex-wrap items-center gap-1.5">
                    <button type="button" @click="shipOpen = true" class="rounded-lg bg-neutral-100 hover:bg-neutral-200 text-neutral-700 font-bold px-3 py-1.5 text-xs transition">🚚 택배비 {{ $order->shipping_fee ? '수정' : '추가' }}</button>
                    <button type="button" @click="{!! $hasPending ? $pendingAlert : 'stmtOpen = true' !!}" class="rounded-lg bg-neutral-100 hover:bg-neutral-200 text-neutral-700 font-bold px-3 py-1.5 text-xs transition">🧾 거래명세서</button>
                    <form method="POST" action="{{ route('portal.hq.orders.statement.email', $order) }}" class="inline"
                          data-ajax-toast="거래명세서를 매장 이메일로 전송했습니다."
                          onsubmit="@if ($hasPending) alert('미확인 단가(싯가) 품목이 있어 거래명세서를 전송할 수 없습니다.\n단가 확정 후 다시 시도하세요.'); return false; @else return confirm('거래명세서 PDF를 매장({{ $order->store->email }})으로 전송합니다.\n진행하시겠습니까?') @endif">
                        @csrf
                        <input type="hidden" name="statement_date" :value="stmtDate">
                        <button type="submit" @if (! $order->store?->email) disabled @endif
                                class="rounded-lg bg-sky-500 hover:bg-sky-600 disabled:opacity-40 text-white font-bold px-3 py-1.5 text-xs transition">📧 {{ $order->statement_emailed_at ? '재전송' : '이메일' }}</button>
                    </form>
                    @if ($taxInvoice)
                        <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 text-emerald-700 font-bold px-3 py-1.5 text-xs">✓ 세금계산서 {{ $taxInvoice->invoice_no }}</span>
                        @if ($taxInvoice->status === 'issued')
                            <form method="POST" action="{{ route('portal.hq.tax_invoices.cancel', $taxInvoice) }}" class="inline"
                                  onsubmit="return confirm('이 세금계산서를 발행취소합니다. 진행하시겠습니까?\n(국세청 전송 완료 후에는 취소되지 않을 수 있습니다.)')">
                                @csrf
                                <button type="submit" class="text-xs font-bold text-rose-500 hover:text-rose-600 underline">발행취소</button>
                            </form>
                        @endif
                    @else
                        <form method="POST" action="{{ route('portal.hq.tax_invoices.issue', $order) }}" class="inline"
                              onsubmit="return confirm('본사 → 매장 세금계산서를 발행합니다.\n수신: {{ $order->store->name }} ({{ $order->store->email }})\n진행하시겠습니까?')">
                            @csrf
                            <button type="submit" @unless ($order->store?->biz_no) title="⚠ 매장 사업자등록번호가 없습니다. 매장 관리에서 먼저 등록하세요." @endunless
                                    class="rounded-lg bg-mango-500 hover:bg-mango-600 text-white font-bold px-3 py-1.5 text-xs transition">🧾 세금계산서 발행</button>
                        </form>
                    @endif
                </div>
            @endunless
        </div>

        {{-- 발주 정보 한 줄 --}}
        <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm mt-3 pt-3 border-t border-neutral-100">
            <span class="inline-flex gap-1.5"><span class="text-neutral-500">발주 매장</span><b>{{ $order->store->name ?? '-' }}</b></span>
            <span class="inline-flex gap-1.5"><span class="text-neutral-500">발주자</span>{{ $order->user->name ?? '-' }}</span>
            <span class="inline-flex gap-1.5"><span class="text-neutral-500">연락처</span>{{ $order->store->phone ?? '-' }}</span>
            <span class="inline-flex gap-1.5 min-w-0"><span class="text-neutral-500 shrink-0">배송지</span><span class="truncate" title="{{ $shipAddr }}">{{ $shipAddr }}</span></span>
        </div>

        {{-- 정산 요약 한 줄 --}}
        <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm mt-2 pt-2 border-t border-neutral-100">
            <span class="inline-flex gap-1.5"><span class="text-neutral-500">매장 출고가</span><b>{{ number_format($order->store_amount) }}원</b></span>
            <span class="inline-flex items-center gap-1.5"><span class="text-neutral-500">택배비</span>{{ number_format($order->shipping_fee) }}원@if ($order->shipping_fee)<span class="text-neutral-400 text-xs">({{ number_format($order->shipping_box_count) }}×{{ number_format($order->shipping_unit_price) }})</span>@endif</span>
            <span class="inline-flex gap-1.5"><span class="text-neutral-500">발주 합계</span><b class="text-mango-700">{{ number_format($order->order_total) }}원</b></span>
            <span class="inline-flex items-center gap-1.5"><span class="text-neutral-500">입금 상태</span>
                @if ($order->paid_at)<span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">💰 입금완료 · {{ $order->paid_at->format('m.d H:i') }}</span>
                @else<span class="text-xs font-bold px-2 py-0.5 rounded-full bg-neutral-100 text-neutral-500">입금대기</span>@endif
            </span>
        </div>

        @if ($order->note)
            <p class="mt-2 text-sm text-neutral-600 bg-neutral-50 rounded-xl px-3 py-2">📝 {{ $order->note }}</p>
        @endif
        @if ($order->status === 'canceled')
            <p class="mt-2 rounded-xl bg-neutral-100 text-neutral-500 text-sm text-center py-2">취소된 발주입니다. 택배비·거래명세서·세금계산서 처리는 제공되지 않습니다.</p>
        @endif
    </div>

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

    {{-- 거래명세서 PDF 모달 팝업 --}}
    <div x-show="stmtOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/50 p-4" @keydown.escape.window="stmtOpen = false">
        <div class="relative mx-auto max-w-3xl my-8 text-neutral-800">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <label class="flex items-center gap-2 rounded-xl bg-white/90 px-3 py-2 text-sm shadow">
                    <span class="font-bold text-neutral-600">발행일자</span>
                    <input type="date" x-model="stmtDate" class="rounded-lg border-neutral-200 text-sm py-1">
                </label>
                <div class="flex items-center gap-2">
                    <a :href="'{{ route('portal.hq.orders.statement.pdf', $order) }}?date=' + stmtDate" target="_blank"
                       class="rounded-xl bg-white/90 hover:bg-white text-neutral-700 font-bold px-4 py-2 text-sm shadow">⬇ PDF 다운로드</a>
                    <button type="button" @click="stmtOpen = false" class="rounded-xl bg-white/90 hover:bg-white text-neutral-700 font-bold px-4 py-2 text-sm shadow">닫기 ✕</button>
                </div>
            </div>
            @include('portal.partials.store-order-statement-document', ['order' => $order, 'editableDate' => true])
        </div>
    </div>
</div>

{{-- 품목: 공급처명 확인 가능 --}}
<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden"
     x-data="{ itemOpen: false, addOpen: false, f: { id: null, name: '', supply: 0, store: 0, qty: 1, unit: '', isSupplier: false },
               get lineStore() { return (this.store || 0) * (this.qty || 0); },
               openItem(d) { this.f = d; this.itemOpen = true; } }">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900 flex items-center justify-between">
        <span>발주 품목 · 공급처 / 배송현황</span>
        <div class="flex items-center gap-3">
            <span class="text-xs font-semibold text-neutral-400">품목명을 클릭해 수정</span>
            @if (in_array($order->status, ['pending', 'processing'], true))
                <button type="button" @click="addOpen = !addOpen" class="rounded-lg bg-mango-500 hover:bg-mango-600 text-white text-xs font-bold px-3 py-1.5">＋ 품목 추가</button>
            @endif
        </div>
    </div>

    @if (in_array($order->status, ['pending', 'processing'], true))
        @error('add')<div class="mx-6 mt-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-2.5 text-sm">{{ $message }}</div>@enderror
        <form method="POST" action="{{ route('portal.hq.orders.items.add', $order) }}" x-show="addOpen" x-cloak
              class="px-6 py-4 bg-mango-50/40 border-b border-neutral-100 flex flex-wrap items-end gap-2">
            @csrf
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-bold text-neutral-500 mb-1">품목</label>
                <select name="product_id" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
                    <option value="">품목 선택</option>
                    @foreach ($addableProducts as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->unit }}{{ $p->supply_type === 'supplier' ? ' · 공급처' : ' · 본사' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="w-24">
                <label class="block text-xs font-bold text-neutral-500 mb-1">수량</label>
                <input type="number" name="qty" min="1" value="1" required class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400 text-sm">
            </div>
            <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-5 py-2 text-sm">추가</button>
            <button type="button" @click="addOpen = false" class="rounded-xl bg-neutral-100 hover:bg-neutral-200 text-neutral-500 font-bold px-4 py-2 text-sm">닫기</button>
        </form>
    @endif
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
            {{-- 품목 삭제 (배송 시작 전) — 별도 form --}}
            @if (in_array($order->status, ['pending', 'processing'], true) && $order->items->count() > 1)
                <form method="POST" :action="'{{ url('portal/hq/orders/'.$order->id.'/items') }}/' + f.id"
                      onsubmit="return confirm('이 품목을 발주에서 삭제할까요? 매장·정산·판매주문에 반영됩니다.')"
                      class="mt-3 pt-3 border-t border-neutral-100">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 font-bold px-4 py-2.5 text-sm transition">🗑 이 품목 발주에서 삭제</button>
                </form>
            @endif
        </div>
    </div>
</div>

{{-- 배송 완료 증빙: 현장 사진 · 매장 담당자 서명 (클릭 시 팝오버 확대) --}}
@if (! empty($order->delivery_photos) || $order->delivery_signature)
<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-4 mt-3" x-data="{ viewer: null }">
    <div class="font-extrabold text-neutral-900 mb-3 flex items-center gap-2">
        <span>📦 배송 완료 증빙</span>
        @if ($order->delivered_at)
            <span class="text-xs font-semibold text-neutral-400">{{ $order->delivered_at->format('Y.m.d H:i') }} 배송완료</span>
        @endif
    </div>
    <div class="flex flex-wrap gap-3">
        @foreach ($order->delivery_photos ?? [] as $i => $p)
            <button type="button" @click="viewer = '{{ asset($p) }}'"
                    class="group relative w-28 h-28 rounded-xl overflow-hidden border border-neutral-200 bg-neutral-50">
                <img src="{{ asset($p) }}" alt="현장사진 {{ $i + 1 }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition">
                <span class="absolute bottom-1 left-1 text-[10px] font-bold text-white bg-black/50 px-1.5 py-0.5 rounded">사진 {{ $i + 1 }}</span>
            </button>
        @endforeach
        @if ($order->delivery_signature)
            <button type="button" @click="viewer = '{{ asset($order->delivery_signature) }}'"
                    class="group relative w-40 h-28 rounded-xl overflow-hidden border border-neutral-200 bg-white">
                <img src="{{ asset($order->delivery_signature) }}" alt="매장 담당자 서명" loading="lazy" class="w-full h-full object-contain p-1">
                <span class="absolute bottom-1 left-1 text-[10px] font-bold text-white bg-black/50 px-1.5 py-0.5 rounded">서명</span>
            </button>
        @endif
    </div>

    {{-- 팝오버(라이트박스) --}}
    <div x-show="viewer" x-cloak @keydown.escape.window="viewer = null"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-6" @click="viewer = null">
        <img :src="viewer" alt="증빙 확대" class="max-w-full max-h-[85vh] rounded-lg shadow-2xl bg-white" @click.stop>
        <button type="button" @click="viewer = null" class="absolute top-4 right-5 text-white text-3xl font-bold leading-none">✕</button>
    </div>
</div>
@endif
@endsection
