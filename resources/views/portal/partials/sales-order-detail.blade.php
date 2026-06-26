{{-- 판매주문 상세 본문. $salesOrder, $routePrefix 필요 --}}
<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 p-7 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-2xl font-black text-neutral-900">{{ $salesOrder->sales_order_no }}</h2>
            <p class="text-sm text-neutral-400 mt-1">원 구매주문 {{ $salesOrder->order->order_no ?? '-' }} · {{ $salesOrder->created_at->format('Y.m.d H:i') }}</p>
        </div>
        @include('portal.partials.lifecycle-status', ['status' => $salesOrder->status, 'label' => $salesOrder->status_label])
    </div>
    <dl class="grid sm:grid-cols-2 gap-x-8 gap-y-3 mt-6 text-sm">
        <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">매장(배송지)</dt><dd class="font-bold">{{ $salesOrder->store->name ?? '-' }}</dd></div>
        <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">연락처</dt><dd>{{ $salesOrder->store->phone ?? '-' }}</dd></div>
        <div class="flex justify-between border-b border-neutral-100 pb-2 sm:col-span-2"><dt class="text-neutral-500 font-semibold">주소</dt><dd class="text-right">{{ $salesOrder->store->address ?? '-' }}</dd></div>
        <div class="flex justify-between border-b border-neutral-100 pb-2"><dt class="text-neutral-500 font-semibold">공급액</dt><dd class="font-bold text-mango-700">{{ number_format($salesOrder->supply_amount) }}원</dd></div>
    </dl>

    @if ($salesOrder->status === 'created')
        <form method="POST" action="{{ route($routePrefix . '.sales_orders.confirm', $salesOrder) }}" class="mt-6">
            @csrf @method('PATCH')
            <button class="rounded-xl bg-mango-500 hover:bg-mango-600 text-white font-bold px-7 py-3 transition">판매주문 확인 (→ 매장 입고예정 생성)</button>
        </form>
    @endif
</div>

<div class="rounded-2xl bg-white shadow-sm border border-neutral-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-100 font-extrabold text-neutral-900">품목</div>
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
            @foreach ($salesOrder->items as $it)
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
