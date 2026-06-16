@extends('portal.layout')
@section('title', '출고 상세')

@section('content')
<div class="flex items-center justify-between mb-5">
    <a href="{{ route($routePrefix . '.shipments.index') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-neutral-500 hover:text-mango-600">← 출고 목록</a>
    <a href="{{ route($routePrefix . '.shipments.statement', $shipment) }}" target="_blank"
       class="rounded-xl bg-neutral-900 hover:bg-mango-600 text-white font-bold px-5 py-2.5 transition">🧾 거래명세서</a>
</div>

<div class="grid lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 rounded-2xl bg-white shadow-sm border border-neutral-100 p-7">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black text-neutral-900">{{ $shipment->shipment_no }}</h2>
                <p class="text-sm text-neutral-400 mt-1">{{ $shipment->created_at->format('Y.m.d H:i') }}</p>
            </div>
            @include('portal.partials.lifecycle-status', ['status' => $shipment->status, 'label' => $shipment->status_label])
        </div>
        <dl class="grid sm:grid-cols-2 gap-x-8 gap-y-3 mt-6 text-sm">
            <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">매장</dt><dd class="font-bold">{{ $shipment->store->name ?? '-' }}</dd></div>
            <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">연락처</dt><dd>{{ $shipment->store->phone ?? '-' }}</dd></div>
            <div class="flex justify-between border-b border-neutral-100 pb-2 sm:col-span-2"><dt class="text-neutral-500 font-semibold">배송지</dt><dd class="text-right">{{ $shipment->store ? ($shipment->store->postcode ? '('.$shipment->store->postcode.') ' : '').$shipment->store->full_delivery_address : '-' }}</dd></div>
            @if ($shipment->tracking_no)
                <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">택배사</dt><dd class="font-bold">{{ $shipment->carrier }}</dd></div>
                <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">송장번호</dt><dd class="font-bold text-mango-700">{{ $shipment->tracking_no }}</dd></div>
            @endif
        </dl>
    </div>

    {{-- 송장 입력 + 출고확정 --}}
    <div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-6">
        @if ($shipment->status === 'created')
            <h3 class="font-extrabold text-neutral-900 mb-4">송장 입력 · 출고확정</h3>
            <form method="POST" action="{{ route($routePrefix . '.shipments.confirm', $shipment) }}" class="space-y-3">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">택배사</label>
                    <input type="text" name="carrier" value="{{ old('carrier') }}" required list="carriers"
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="CJ대한통운">
                    <datalist id="carriers"><option value="CJ대한통운"><option value="한진택배"><option value="롯데택배"><option value="우체국택배"><option value="로젠택배"></datalist>
                </div>
                <div>
                    <label class="block text-sm font-bold text-neutral-700 mb-1.5">송장번호</label>
                    <input type="text" name="tracking_no" value="{{ old('tracking_no') }}" required
                           class="w-full rounded-xl border-neutral-200 focus:border-mango-400 focus:ring-mango-400" placeholder="0000-0000-0000">
                </div>
                <button class="w-full rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold py-3 transition">출고확정 (배송시작 · 매장 알림)</button>
                <p class="text-[11px] text-neutral-400">확정 시 매장에 배송시작 + 송장 정보가 전달되고 FCM 푸시가 전송됩니다.</p>
            </form>
        @elseif ($shipment->status === 'confirmed')
            <div class="text-center py-6">
                <div class="text-4xl mb-2">🚚</div>
                <p class="font-bold text-sky-700">배송중</p>
                <p class="text-sm text-neutral-500 mt-1">{{ $shipment->confirmed_at?->format('Y.m.d H:i') }} 출고확정</p>
                <p class="text-sm text-neutral-400 mt-1">매장 입고완료 대기</p>
            </div>
        @else
            <div class="text-center py-6">
                <div class="text-4xl mb-2">✅</div>
                <p class="font-bold text-emerald-700">입고완료</p>
                <p class="text-sm text-neutral-500 mt-1">{{ $shipment->received_at?->format('Y.m.d H:i') }}</p>
            </div>
        @endif
    </div>
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">출고 품목</div>
    <table class="w-full text-sm">
        <thead class="bg-neutral-50 text-neutral-500">
            <tr>
                <th class="text-left font-semibold px-6 py-3">품목</th>
                <th class="text-left font-semibold px-6 py-3">단위</th>
                <th class="text-right font-semibold px-6 py-3">수량</th>
                <th class="text-right font-semibold px-6 py-3">공급액</th>
                <th class="text-left font-semibold px-6 py-3">배송</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-100">
            @foreach ($shipment->items as $it)
                <tr>
                    <td class="px-6 py-3.5 font-bold text-neutral-900">{{ $it->product_name }}</td>
                    <td class="px-6 py-3.5 text-neutral-500">{{ $it->unit }}</td>
                    <td class="px-6 py-3.5 text-right">{{ number_format($it->qty) }}</td>
                    <td class="px-6 py-3.5 text-right font-semibold">{{ number_format($it->supply_line_amount) }}원</td>
                    <td class="px-6 py-3.5">@include('portal.partials.fulfillment-status', ['status' => $it->fulfillment_status, 'label' => $it->fulfillment_label])</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
