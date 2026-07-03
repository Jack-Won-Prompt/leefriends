<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\Shipment;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 매장 입고 — 입고예정(확인된 판매주문) + 배송중(출고확정) + 입고처리.
 */
class InboundController extends Controller
{
    /**
     * GET /api/v1/inbound
     */
    public function index(Request $request): JsonResponse
    {
        $storeId = $this->storeId($request);

        $expected = SalesOrder::where('store_id', $storeId)
            ->where('status', 'confirmed')
            ->with(['supplier', 'order'])
            ->latest()
            ->get()
            ->map(fn (SalesOrder $so) => [
                'id' => $so->id,
                'sales_order_no' => $so->sales_order_no,
                'order_no' => $so->order?->order_no,
                'seller' => $so->seller_type === 'supplier' ? $so->supplier?->name : '본사',
                'item_count' => (int) $so->item_count,
                'store_amount' => (int) $so->store_amount,
                'created_at' => $so->created_at?->format('Y-m-d H:i'),
            ]);

        $inTransit = Shipment::where('store_id', $storeId)
            ->whereIn('status', ['confirmed', 'delivered'])
            ->with('supplier')
            ->latest('confirmed_at')
            ->get()
            ->map(fn (Shipment $s) => $this->shipmentSummary($s));

        return response()->json([
            'expected' => $expected->values(),
            'in_transit' => $inTransit->values(),
        ]);
    }

    /**
     * GET /api/v1/shipments/{shipment}  — 출고(배송) 상세
     */
    public function show(Request $request, Shipment $shipment): JsonResponse
    {
        abort_unless($shipment->store_id === $this->storeId($request), 403);
        $shipment->load(['items', 'items.supplyProduct', 'supplier']);

        return response()->json([
            'data' => array_merge($this->shipmentSummary($shipment), [
                'note' => $shipment->note,
                'received_at' => $shipment->received_at?->format('Y-m-d H:i'),
                'items' => $shipment->items->map(fn ($it) => [
                    'id' => $it->id,
                    'product_name' => $it->product_name,
                    'image' => $it->supplyProduct?->image ? asset($it->supplyProduct->image) : null,
                    'unit' => $it->unit,
                    'qty' => (int) $it->qty,
                ])->values(),
            ]),
        ]);
    }

    /**
     * POST /api/v1/shipments/{shipment}/receive  — 인수확인 → 재고 반영
     */
    public function receive(Request $request, Shipment $shipment, InventoryService $inventory): JsonResponse
    {
        abort_unless($shipment->store_id === $this->storeId($request), 403);

        $inventory->receiveShipment($shipment, $request->user()->id);

        return response()->json([
            'message' => '입고가 완료되었습니다. 재고에 반영되었습니다.',
            'data' => $this->shipmentSummary($shipment->fresh(['supplier'])),
        ]);
    }

    private function shipmentSummary(Shipment $s): array
    {
        return [
            'id' => $s->id,
            'shipment_no' => $s->shipment_no,
            'status' => $s->status,
            'status_label' => Shipment::STATUSES[$s->status] ?? $s->status,
            'seller' => $s->seller_type === 'supplier' ? $s->supplier?->name : '본사',
            'carrier' => $s->carrier,
            'tracking_no' => $s->tracking_no,
            'item_count' => (int) $s->item_count,
            'total_qty' => (int) $s->total_qty,
            'confirmed_at' => $s->confirmed_at?->format('Y-m-d H:i'),
            'delivered_at' => $s->delivered_at?->format('Y-m-d H:i'),
        ];
    }

    private function storeId(Request $request): int
    {
        $id = $request->user()->store_id;
        abort_unless($id, 403, '연결된 매장이 없는 계정입니다.');

        return $id;
    }
}
