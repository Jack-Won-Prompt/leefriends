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
            $order->load(['store', 'items' => $mine]);
        } else {
            $order->load(['store', 'items']);
        }

        return response()->json([
            'data' => array_merge($this->summary($order, $type), [
                'note' => $order->note,
                'items' => $order->items->map(fn (OrderItem $it) => [
                    'id' => $it->id,
                    'product_name' => $it->product_name,
                    'unit' => $it->unit,
                    'qty' => (int) $it->qty,
                    'supplier_name' => $it->supplier_name,
                    'supply_type' => $it->supply_type,
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
            'created_at' => $o->created_at?->format('Y-m-d H:i'),
        ];
    }
}
