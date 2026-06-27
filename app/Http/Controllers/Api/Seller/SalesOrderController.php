<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderChange;
use App\Models\OrderItem;
use App\Models\SalesOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 본사/공급처 판매주문 — 조회 / 확인(confirm).
 */
class SalesOrderController extends Controller
{
    use ResolvesSeller;

    /**
     * GET /api/v1/seller/sales-orders?status=all|created|confirmed|shipped|received|canceled
     */
    public function index(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);
        $status = $request->query('status', 'all');

        $query = SalesOrder::forSeller($type, $sid)->with(['store', 'order'])->latest();
        if (array_key_exists($status, SalesOrder::STATUSES)) {
            $query->where('status', $status);
        }
        $orders = $query->paginate(20);

        return response()->json([
            'data' => $orders->getCollection()->map(fn (SalesOrder $so) => $this->summary($so))->values(),
            'meta' => [
                'status' => $status,
                'statuses' => collect(SalesOrder::STATUSES)
                    ->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/seller/sales-orders/{salesOrder}
     */
    public function show(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        $this->authorize($request, $salesOrder);
        $salesOrder->load(['store', 'order', 'items']);

        return response()->json(['data' => $this->detail($salesOrder)]);
    }

    /**
     * PATCH /api/v1/seller/sales-orders/{salesOrder}/confirm
     */
    public function confirm(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        [$type, $sid] = $this->seller($request);
        $this->authorize($request, $salesOrder);

        if ($salesOrder->status !== 'created') {
            return response()->json(['message' => '이미 확인되었거나 처리할 수 없는 판매주문입니다.'], 409);
        }

        if (OrderChange::forSeller($type, $sid)->pending()->where('order_id', $salesOrder->order_id)->exists()) {
            return response()->json([
                'message' => '해당 주문에 미반영된 매장 변경이 있습니다. 웹 포털에서 변경 확인 후 진행하세요.',
            ], 409);
        }

        // 싯가 품목 단가 미확정이면 확인 차단
        if (OrderItem::where('sales_order_id', $salesOrder->id)->where('price_pending', true)->exists()) {
            return response()->json([
                'message' => '싯가 품목의 단가가 확정되지 않았습니다. «받은 발주»에서 단가를 먼저 확정하세요.',
            ], 409);
        }

        $salesOrder->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        return response()->json([
            'message' => '판매주문을 확인했습니다. 매장에 입고예정 정보가 생성되었습니다.',
            'data' => $this->summary($salesOrder->fresh(['store', 'order'])),
        ]);
    }

    private function authorize(Request $request, SalesOrder $so): void
    {
        [$type, $sid] = $this->seller($request);
        abort_unless($so->seller_type === $type && $so->supplier_id == $sid, 403);
    }

    private function summary(SalesOrder $so): array
    {
        return [
            'id' => $so->id,
            'sales_order_no' => $so->sales_order_no,
            'status' => $so->status,
            'status_label' => SalesOrder::STATUSES[$so->status] ?? $so->status,
            'seller_type' => $so->seller_type, // hq=매장가 기준 / supplier=공급가 기준
            'store_name' => $so->store?->name,
            'order_no' => $so->order?->order_no,
            'item_count' => (int) $so->item_count,
            'store_amount' => (int) $so->store_amount,
            'supply_amount' => (int) $so->supply_amount,
            'confirmed_at' => $so->confirmed_at?->format('Y-m-d H:i'),
            'created_at' => $so->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function detail(SalesOrder $so): array
    {
        return array_merge($this->summary($so), [
            'items' => $so->items->map(fn (OrderItem $it) => [
                'id' => $it->id,
                'product_name' => $it->product_name,
                'unit' => $it->unit,
                'qty' => (int) $it->qty,
                'supply_unit_price' => (int) $it->supply_unit_price,
                'supply_line_amount' => (int) $it->supply_line_amount,
                'store_line_amount' => (int) $it->store_line_amount,
                'fulfillment_status' => $it->fulfillment_status,
            ])->values(),
        ]);
    }
}
