<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 본사/공급처가 받은 매장 발주 조회.
 *  - 본사: 전체 주문
 *  - 공급처: 자사 품목이 포함된 주문 (자사 품목만 노출)
 */
class OrderController extends Controller
{
    use ResolvesSeller;

    /**
     * GET /api/v1/seller/orders?status=all|pending|processing|shipping|completed|canceled
     */
    public function index(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);
        $status = $request->query('status', 'all');

        if ($type === 'supplier') {
            $mine = fn ($q) => $q->where('supplier_id', $sid)->where('supply_type', 'supplier');
            $query = Order::whereHas('items', $mine)->with(['store', 'items' => $mine]);
        } else {
            $query = Order::with('store')->withCount('items');
        }
        $query->latest();
        if (array_key_exists($status, Order::STATUSES)) {
            $query->where('status', $status);
        }
        $orders = $query->paginate(20);

        return response()->json([
            'data' => $orders->getCollection()->map(fn (Order $o) => $this->summary($o, $type))->values(),
            'meta' => [
                'status' => $status,
                'statuses' => collect(Order::STATUSES)
                    ->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/seller/orders/{order}
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        if ($type === 'supplier') {
            $mine = fn ($q) => $q->where('supplier_id', $sid)->where('supply_type', 'supplier');
            abort_unless($order->items()->where($mine)->exists(), 403);
            $order->load(['store', 'items' => $mine, 'items.supplyProduct']);
        } else {
            $order->load(['store', 'items', 'items.supplyProduct']);
        }

        return response()->json([
            'data' => array_merge($this->summary($order, $type), [
                'note' => $order->note,
                'store_email' => $order->store?->email,
                'is_sample' => ($order->order_type ?? 'normal') === 'sample',
                'tax_invoiced' => (bool) $order->tax_invoice_id,
                'statement_emailed' => $order->statement_emailed_at !== null,
                'statement_email_count' => (int) $order->statement_email_count,
                'has_pending_price' => $order->items->where('price_pending', true)->isNotEmpty(),
                'shipping_box_count' => (int) $order->shipping_box_count,
                'shipping_unit_price' => (int) $order->shipping_unit_price,
                'shipping_fee' => (int) $order->shipping_fee,
                'order_total' => (int) $order->order_total,
                'items' => $order->items->map(fn (OrderItem $it) => [
                    'id' => $it->id,
                    'product_name' => $it->product_name,
                    'image' => $it->supplyProduct?->image ? asset($it->supplyProduct->image) : null,
                    'unit' => $it->unit,
                    'qty' => (int) $it->qty,
                    'supplier_name' => $it->supplier_name,
                    'supply_type' => $it->supply_type,
                    'supply_unit_price' => (int) $it->supply_unit_price,
                    'store_unit_price' => (int) $it->store_unit_price,
                    'store_line_amount' => (int) $it->store_line_amount,
                    'supply_line_amount' => (int) $it->supply_line_amount,
                    'price_pending' => (bool) $it->price_pending,
                    'fulfillment_status' => $it->fulfillment_status,
                ])->values(),
            ]),
        ]);
    }

    /**
     * PATCH /api/v1/seller/orders/{order}/items/{item}  — 본사 직공급 품목 배송상태 처리 (본사 전용)
     * body: { fulfillment_status: pending|shipping|delivered }
     */
    public function updateItem(Request $request, Order $order, OrderItem $item): JsonResponse
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');
        abort_unless($item->order_id === $order->id && $item->supply_type === 'hq', 403,
            '본사 직공급 품목만 처리할 수 있습니다.');

        $data = $request->validate([
            'fulfillment_status' => ['required', 'in:pending,shipping,delivered'],
        ]);

        $item->fulfillment_status = $data['fulfillment_status'];
        $item->shipped_at = in_array($data['fulfillment_status'], ['shipping', 'delivered'], true)
            ? ($item->shipped_at ?? now())
            : null;
        $item->save();

        $order->syncStatus();

        return response()->json(['message' => '본사 공급 품목의 배송상태가 변경되었습니다.']);
    }

    /**
     * PATCH /api/v1/seller/orders/{order}/items/{item}/edit — 품목 공급가/출고가/수량 수정 (본사)
     * body: { supply_unit_price?, store_unit_price, qty }
     */
    public function editItem(Request $request, Order $order, OrderItem $item, NotificationService $notifications): JsonResponse
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');
        abort_unless($item->order_id === $order->id, 403);

        $data = $request->validate([
            'supply_unit_price' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'store_unit_price' => ['required', 'integer', 'min:0', 'max:100000000'],
            'qty' => ['required', 'integer', 'min:1', 'max:99999'],
        ]);

        $supply = (int) ($data['supply_unit_price'] ?? $item->supply_unit_price);
        $store = (int) $data['store_unit_price'];
        $qty = (int) $data['qty'];

        $item->update([
            'supply_unit_price' => $supply,
            'store_unit_price' => $store,
            'qty' => $qty,
            'supply_line_amount' => $supply * $qty,
            'store_line_amount' => $store * $qty,
            'price_pending' => false,
        ]);

        $order->recomputeAmounts();

        try {
            $notifications->notifyStore(
                (int) $order->store_id,
                'order_item_updated',
                '📝 발주 품목 변경',
                "{$item->product_name} 내용이 본사에서 수정되었습니다. (수량 {$qty}, 출고가 ".number_format($store).'원)',
                ['order_id' => $order->id],
            );
        } catch (\Throwable $e) {
            report($e);
        }

        $order->refresh();

        return response()->json([
            'message' => "«{$item->product_name}» 품목을 수정했습니다.",
            'data' => [
                'qty' => $qty,
                'supply_unit_price' => $supply,
                'store_unit_price' => $store,
                'store_line_amount' => $store * $qty,
                'supply_line_amount' => $supply * $qty,
                'order_store_amount' => (int) $order->store_amount,
                'order_total' => (int) $order->order_total,
            ],
        ]);
    }

    /**
     * PATCH /api/v1/seller/orders/{order}/items/{item}/price — 싯가 품목 단가 확정 (본사 전용)
     * body: { store_unit_price }
     */
    public function setItemPrice(
        Request $request,
        Order $order,
        OrderItem $item,
        NotificationService $notifications
    ): JsonResponse {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');
        abort_unless($item->order_id === $order->id, 403);
        abort_unless($item->price_pending, 422, '단가 확정 대기 중인 품목이 아닙니다.');

        $data = $request->validate([
            'store_unit_price' => ['required', 'integer', 'min:1', 'max:100000000'],
        ], ['store_unit_price.required' => '단가를 입력해 주세요.']);

        $price = (int) $data['store_unit_price'];
        $item->update([
            'store_unit_price' => $price,
            'store_line_amount' => $price * $item->qty,
            'price_pending' => false,
        ]);

        $order->recomputeAmounts();

        try {
            $notifications->notifyStore(
                (int) $order->store_id,
                'market_price_set',
                '🥭 싯가 가격 확정',
                "{$item->product_name} 단가가 ".number_format($price).'원으로 확정되었습니다.',
                ['order_id' => $order->id],
            );
        } catch (\Throwable $e) {
            report($e);
        }

        $order->refresh();

        return response()->json([
            'message' => "«{$item->product_name}» 단가를 확정했습니다.",
            'data' => [
                'store_unit_price' => $price,
                'store_line_amount' => $price * $item->qty,
                'order_store_amount' => (int) $order->store_amount,
                'order_has_pending' => $order->hasPendingPrice(),
            ],
        ]);
    }

    /**
     * POST /api/v1/seller/orders/{order}/tax-invoice — 이 발주로 세금계산서 발행 (본사 → 매장)
     */
    public function issueForOrder(Request $request, Order $order, \App\Services\TaxInvoice\TaxInvoiceIssueService $service): JsonResponse
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');

        if (($order->order_type ?? 'normal') === 'sample') {
            return response()->json(['message' => '샘플 주문은 세금계산서를 발행할 수 없습니다.'], 422);
        }
        if ($order->tax_invoice_id) {
            return response()->json(['message' => '이미 이 발주에 대한 세금계산서가 발행되었습니다.'], 409);
        }
        if ($order->hasPendingPrice()) {
            return response()->json(['message' => '싯가 품목 단가가 확정되지 않았습니다. 먼저 단가를 확정하세요.'], 409);
        }

        try {
            $invoices = $service->hqToStore($order);
        } catch (\Throwable $e) {
            return response()->json(['message' => '세금계산서 발행 실패: '.$e->getMessage()], 422);
        }

        $nos = $invoices->pluck('invoice_no')->implode(', ');

        return response()->json([
            'message' => $invoices->count() > 1
                ? "세금계산서·계산서 2건을 발행했습니다. (번호 {$nos})"
                : "세금계산서를 발행했습니다. (번호 {$nos})",
            'data' => ['invoice_ids' => $invoices->pluck('id')->values()],
        ], 201);
    }

    /**
     * PATCH /api/v1/seller/orders/{order}/shipping — 택배비(박스·단가) 등록/수정 (본사)
     * body: { shipping_box_count, shipping_unit_price }
     */
    public function updateShipping(Request $request, Order $order): JsonResponse
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');

        $data = $request->validate([
            'shipping_box_count' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'shipping_unit_price' => ['nullable', 'integer', 'min:0', 'max:9999999'],
        ]);

        $box = (int) ($data['shipping_box_count'] ?? 0);
        $unit = (int) ($data['shipping_unit_price'] ?? 0);

        $order->update([
            'shipping_box_count' => $box ?: null,
            'shipping_unit_price' => $unit ?: null,
            'shipping_fee' => $box * $unit,
        ]);

        return response()->json([
            'message' => '택배비를 저장했습니다. (발주 합계에 반영)',
            'data' => [
                'shipping_box_count' => $box,
                'shipping_unit_price' => $unit,
                'shipping_fee' => $box * $unit,
                'order_total' => (int) $order->fresh()->order_total,
            ],
        ]);
    }

    /**
     * POST /api/v1/seller/orders/{order}/statement-email — 발주 거래명세서 PDF를 매장 이메일로 전송 (본사)
     */
    public function statementEmail(Request $request, Order $order): JsonResponse
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');

        if (($order->order_type ?? 'normal') === 'sample') {
            return response()->json(['message' => '샘플 주문은 거래명세서를 제공하지 않습니다.'], 422);
        }

        $order->load(['items', 'store']);
        $to = $order->store?->email;
        if (! $to) {
            return response()->json(['message' => '매장 이메일이 없습니다. 매장 관리에서 이메일을 먼저 등록하세요.'], 422);
        }

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.print.order-statement-pdf', compact('order'))->setPaper('a4');
            \Illuminate\Support\Facades\Mail::to($to)->send(
                new \App\Mail\OrderStatementMail($order, $pdf->output(), '거래명세서_'.$order->order_no.'.pdf')
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => '거래명세서 전송 실패: '.$e->getMessage()], 422);
        }

        $order->update(['statement_emailed_at' => now(), 'statement_email_count' => $order->statement_email_count + 1]);

        return response()->json(['message' => "거래명세서를 매장({$to})으로 전송했습니다."]);
    }

    /** 입금요청 SMS 전송 + 주문 상태 접수(pending). 본사 전용. (웹 Portal\Hq\OrderController@paymentRequest 와 동일) */
    public function paymentRequest(Request $request, Order $order, \App\Services\Order\PaymentRequestSms $sms): JsonResponse
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');

        if (($order->order_type ?? 'normal') === 'sample') {
            return response()->json(['message' => '샘플 주문은 입금요청을 보내지 않습니다.'], 422);
        }
        if ($order->status === 'canceled') {
            return response()->json(['message' => '취소된 발주입니다.'], 422);
        }

        try {
            $sms->dispatch($order);
        } catch (\Throwable $e) {
            return response()->json(['message' => '입금요청 SMS 전송 실패: '.$e->getMessage()], 422);
        }

        if ($order->status !== 'pending') {
            $order->update(['status' => 'pending']);
        }

        return response()->json(['message' => '입금요청 SMS를 전송했습니다. ('.($order->store->name ?? '매장').')']);
    }

    private function summary(Order $o, string $type): array
    {
        // 공급처는 자사 품목 합계만 의미가 있으므로 로드된 items 기준 집계
        $itemCount = $o->items_count ?? $o->items->count();
        $supply = $type === 'supplier'
            ? (int) $o->items->sum('supply_line_amount')
            : (int) $o->supply_amount;

        return [
            'id' => $o->id,
            'order_no' => $o->order_no,
            'status' => $o->status,
            'status_label' => Order::STATUSES[$o->status] ?? $o->status,
            'store_name' => $o->store?->name,
            'item_count' => $itemCount,
            'store_amount' => (int) $o->store_amount,
            'supply_amount' => $supply,
            'paid' => $o->isPaid(),
            'paid_at' => $o->paid_at?->format('Y-m-d H:i'),
            'created_at' => $o->created_at?->format('Y-m-d H:i'),
        ];
    }
}
