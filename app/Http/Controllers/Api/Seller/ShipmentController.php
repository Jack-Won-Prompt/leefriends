<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\OrderChange;
use App\Models\OrderItem;
use App\Models\Shipment;
use App\Models\Store;
use App\Services\Fulfillment\ShipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 본사/공급처 출고 — 조회 / 생성 / 확정. 웹 BaseShipmentController 와 동일 규칙.
 */
class ShipmentController extends Controller
{
    use ResolvesSeller;

    /**
     * GET /api/v1/seller/shipments?status=all|created|confirmed|received|canceled
     */
    public function index(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);
        $status = $request->query('status', 'all');
        $store = $request->query('store', 'all');

        $query = Shipment::forSeller($type, $sid)->with('store')->latest();
        if (array_key_exists($status, Shipment::STATUSES)) {
            $query->where('status', $status);
        }
        if ($store !== 'all' && is_numeric($store)) {
            $query->where('store_id', (int) $store);
        }
        $shipments = $query->paginate(20);

        // 이 판매자의 출고가 있는 매장 목록 (매장 필터 드롭다운용)
        $stores = Store::whereIn('id', Shipment::forSeller($type, $sid)->distinct()->pluck('store_id'))
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn ($s) => ['key' => (string) $s->id, 'label' => $s->name])->values();

        return response()->json([
            'data' => $shipments->getCollection()->map(fn (Shipment $s) => $this->summary($s))->values(),
            'meta' => [
                'status' => $status,
                'statuses' => collect(Shipment::STATUSES)
                    ->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
                'store' => $store,
                'stores' => $stores,
                'current_page' => $shipments->currentPage(),
                'last_page' => $shipments->lastPage(),
                'total' => $shipments->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/seller/shipments/candidates
     * 확인된 판매주문의 미출고 품목 → 매장별 그룹 (출고 생성용).
     */
    public function candidates(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        $grouped = OrderItem::whereNull('shipment_id')
            ->whereHas('salesOrder', fn ($q) => $q->forSeller($type, $sid)->where('status', 'confirmed'))
            ->with(['order', 'salesOrder', 'supplyProduct'])
            ->get()
            ->groupBy(fn ($i) => $i->order->store_id);

        $stores = Store::whereIn('id', $grouped->keys())->get()->keyBy('id');

        $data = $grouped->map(fn ($items, $storeId) => [
            'store_id' => (int) $storeId,
            'store_name' => $stores[$storeId]?->name,
            'items' => $items->map(fn (OrderItem $it) => [
                'id' => $it->id,
                'product_name' => $it->product_name,
                'image' => $it->supplyProduct?->image ? asset($it->supplyProduct->image) : null,
                'unit' => $it->unit,
                'qty' => (int) $it->qty,
                'order_no' => $it->order?->order_no,
                'sales_order_no' => $it->salesOrder?->sales_order_no,
            ])->values(),
        ])->values();

        return response()->json(['data' => $data]);
    }

    /**
     * POST /api/v1/seller/shipments
     * body: { store_id, items: [order_item_id,...], note? }
     */
    public function store(Request $request, ShipmentService $service): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        $data = $request->validate([
            'store_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['integer'],
            'note' => ['nullable', 'string', 'max:500'],
        ], ['items.required' => '출고할 품목을 선택해 주세요.']);

        $orderIds = OrderItem::whereIn('id', $data['items'])->distinct()->pluck('order_id');
        if (OrderChange::forSeller($type, $sid)->pending()->whereIn('order_id', $orderIds)->exists()) {
            return response()->json([
                'message' => '선택한 주문에 미반영된 매장 변경이 있습니다. 웹 포털에서 변경 확인 후 출고하세요.',
            ], 409);
        }

        try {
            $shipment = $service->create($type, $sid, (int) $data['store_id'], $data['items'], $data['note'] ?? null);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage() ?: '출고 생성에 실패했습니다.'], 400);
        }

        return response()->json([
            'message' => '출고가 생성되었습니다. 송장 입력 후 출고확정하세요.',
            'data' => $this->detail($shipment->fresh(['store', 'items'])),
        ], 201);
    }

    /**
     * GET /api/v1/seller/shipments/{shipment}
     */
    public function show(Request $request, Shipment $shipment): JsonResponse
    {
        $this->authorize($request, $shipment);
        $shipment->load(['store', 'items']);

        return response()->json(['data' => $this->detail($shipment)]);
    }

    /**
     * PATCH /api/v1/seller/shipments/{shipment}/confirm
     * body: { carrier, tracking_no }
     */
    public function confirm(Request $request, Shipment $shipment, ShipmentService $service): JsonResponse
    {
        $this->authorize($request, $shipment);

        $data = $request->validate([
            'carrier' => ['required', 'string', 'max:50'],
            'tracking_no' => ['nullable', 'string', 'max:50'],
        ], [
            'carrier.required' => '택배사를 선택해 주세요.',
        ]);

        // 직접 배송이면 송장번호 불필요, 그 외에는 필수
        $isDirect = Courier::where('name', $data['carrier'])->where('is_direct', true)->exists();
        if (! $isDirect && empty($data['tracking_no'])) {
            return response()->json(['message' => '송장번호를 입력해 주세요.', 'errors' => ['tracking_no' => ['송장번호를 입력해 주세요.']]], 422);
        }

        try {
            $service->confirm($shipment, $data['carrier'], $data['tracking_no'] ?? '');
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage() ?: '출고 확정에 실패했습니다.'], 400);
        }

        app(\App\Services\Order\OrderStatusSms::class)->shipped($shipment->loadMissing('store'));

        return response()->json([
            'message' => '출고가 확정되었습니다. 매장에 배송시작 알림을 전송했습니다.',
            'data' => $this->detail($shipment->fresh(['store', 'items'])),
        ]);
    }

    /**
     * PATCH /api/v1/seller/shipments/{shipment}/deliver
     * 배송중 → 배송완료 (본사 처리).
     */
    public function deliver(Request $request, Shipment $shipment, ShipmentService $service): JsonResponse
    {
        $this->authorize($request, $shipment);

        try {
            $service->deliver($shipment);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage() ?: '배송완료 처리에 실패했습니다.'], 400);
        }

        app(\App\Services\Order\OrderStatusSms::class)->delivered($shipment->loadMissing('store'));

        return response()->json([
            'message' => '배송완료로 처리했습니다. 매장에 도착 알림을 전송했습니다.',
            'data' => $this->detail($shipment->fresh(['store', 'items'])),
        ]);
    }

    /** 택배사 목록 (출고 확정 드롭다운용, 직접 배송 포함) */
    public function couriers(): JsonResponse
    {
        return response()->json([
            'data' => Courier::active()->ordered()->get(['id', 'name', 'is_direct'])->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'is_direct' => (bool) $c->is_direct,
            ]),
        ]);
    }

    private function authorize(Request $request, Shipment $shipment): void
    {
        [$type, $sid] = $this->seller($request);
        abort_unless($shipment->seller_type === $type && $shipment->supplier_id == $sid, 403);
    }

    private function summary(Shipment $s): array
    {
        return [
            'id' => $s->id,
            'shipment_no' => $s->shipment_no,
            'status' => $s->status,
            'status_label' => Shipment::STATUSES[$s->status] ?? $s->status,
            'store_name' => $s->store?->name,
            'carrier' => $s->carrier,
            'tracking_no' => $s->tracking_no,
            'item_count' => (int) $s->item_count,
            'total_qty' => (int) $s->total_qty,
            'confirmed_at' => $s->confirmed_at?->format('Y-m-d H:i'),
            'delivered_at' => $s->delivered_at?->format('Y-m-d H:i'),
            'created_at' => $s->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function detail(Shipment $s): array
    {
        $s->loadMissing('items.supplyProduct');

        return array_merge($this->summary($s), [
            'note' => $s->note,
            'items' => $s->items->map(fn (OrderItem $it) => [
                'id' => $it->id,
                'product_name' => $it->product_name,
                'image' => $it->supplyProduct?->image ? asset($it->supplyProduct->image) : null,
                'unit' => $it->unit,
                'qty' => (int) $it->qty,
            ])->values(),
        ]);
    }
}
