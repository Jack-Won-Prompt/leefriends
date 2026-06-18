<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderChange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 매장 주문 변경(수정/취소) 확인·반영 — 본사/공급처.
 * 미반영 변경이 있으면 판매주문 확인·출고가 차단되므로 여기서 반영한다.
 */
class OrderChangeController extends Controller
{
    use ResolvesSeller;

    /**
     * GET /api/v1/seller/order-changes
     */
    public function index(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        $changes = OrderChange::forSeller($type, $sid)
            ->with('store')
            ->orderByRaw('acknowledged_at is null desc')
            ->latest()
            ->paginate(30);

        $pending = OrderChange::forSeller($type, $sid)->pending()->count();

        return response()->json([
            'data' => $changes->getCollection()->map(fn (OrderChange $c) => [
                'id' => $c->id,
                'order_no' => $c->order_no,
                'change_type' => $c->change_type,
                'type_label' => OrderChange::TYPES[$c->change_type] ?? $c->change_type,
                'store_name' => $c->store?->name,
                'summary' => $c->summary,
                'acknowledged' => $c->acknowledged_at !== null,
                'created_at' => $c->created_at?->format('Y-m-d H:i'),
            ])->values(),
            'meta' => ['pending' => $pending],
        ]);
    }

    /**
     * POST /api/v1/seller/order-changes/{change}/ack
     */
    public function ack(Request $request, OrderChange $change): JsonResponse
    {
        [$type, $sid] = $this->seller($request);
        abort_unless($change->seller_type === $type && $change->supplier_id == $sid, 403);

        if (! $change->acknowledged_at) {
            $change->update(['acknowledged_at' => now(), 'acknowledged_by' => $request->user()->id]);
        }

        return response()->json(['message' => '변경을 확인(반영)했습니다.']);
    }

    /**
     * POST /api/v1/seller/order-changes/ack-all
     */
    public function ackAll(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        OrderChange::forSeller($type, $sid)->pending()
            ->update(['acknowledged_at' => now(), 'acknowledged_by' => $request->user()->id]);

        return response()->json(['message' => '모든 변경을 확인(반영)했습니다.']);
    }
}
