<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplyProduct;
use App\Models\User;
use App\Services\Inventory\HqStockService;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 구매발주 — 본사가 공급처에 상품/원자재 발주. 본사(생성·입고·취소) + 공급사(확인).
 */
class PurchaseOrderController extends Controller
{
    use ResolvesSeller;

    /** GET /api/v1/seller/purchase-orders?status=all|ordered|confirmed|received|canceled */
    public function index(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);
        $status = $request->query('status', 'all');

        $query = ($type === 'supplier')
            ? PurchaseOrder::where('supplier_id', $sid)
            : PurchaseOrder::query();
        $query->with('items')->latest();
        if (array_key_exists($status, PurchaseOrder::STATUSES)) {
            $query->where('status', $status);
        }
        $pos = $query->paginate(20);

        return response()->json([
            'data' => $pos->getCollection()->map(fn (PurchaseOrder $po) => $this->row($po))->values(),
            'meta' => [
                'role' => $type,
                'status' => $status,
                'statuses' => collect(PurchaseOrder::STATUSES)
                    ->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
                'current_page' => $pos->currentPage(),
                'last_page' => $pos->lastPage(),
                'total' => $pos->total(),
            ],
        ]);
    }

    /** GET /api/v1/seller/purchase-orders/create-data — 생성 폼용 공급처·품목 (본사 전용) */
    public function createData(Request $request): JsonResponse
    {
        $this->hq($request);

        $suppliers = Supplier::orderBy('name')->get(['id', 'name'])
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name]);

        $products = SupplyProduct::where('supply_type', 'supplier')->where('is_active', true)
            ->orderBy('name')->get(['id', 'supplier_id', 'name', 'unit', 'supply_price'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'supplier_id' => $p->supplier_id,
                'name' => $p->name,
                'unit' => $p->unit,
                'supply_price' => (int) $p->supply_price,
            ]);

        return response()->json(['data' => ['suppliers' => $suppliers, 'products' => $products]]);
    }

    /** POST /api/v1/seller/purchase-orders  body: { supplier_id, note?, items:[{product_id, qty}] } */
    public function store(Request $request): JsonResponse
    {
        $this->hq($request);

        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:supply_products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99999'],
        ], ['items.required' => '발주할 품목을 1개 이상 담아주세요.']);

        $supplier = Supplier::findOrFail($data['supplier_id']);
        $products = SupplyProduct::where('supplier_id', $supplier->id)->where('supply_type', 'supplier')
            ->whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');

        $po = DB::transaction(function () use ($data, $supplier, $products, $request) {
            $po = PurchaseOrder::create([
                'po_no' => $this->generatePoNo(),
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'status' => 'ordered',
                'note' => $data['note'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $total = 0;
            foreach ($data['items'] as $it) {
                $p = $products[$it['product_id']] ?? null;
                if (! $p) {
                    continue;
                }
                $price = (int) $p->supply_price;
                $qty = (int) $it['qty'];
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'supply_product_id' => $p->id,
                    'product_name' => $p->name,
                    'unit' => $p->unit,
                    'qty' => $qty,
                    'unit_price' => $price,
                    'line_amount' => $price * $qty,
                ]);
                $total += $price * $qty;
            }
            $po->update(['total_amount' => $total]);

            return $po;
        });

        $this->notifySupplier($supplier->id, '📦 신규 구매발주 도착',
            "본사에서 구매발주 «{$po->po_no}»를 등록했습니다. (".number_format($po->total_amount).'원)',
            ['purchase_order_id' => $po->id]);

        return response()->json([
            'message' => "구매발주 «{$po->po_no}»를 등록하고 공급처에 전송했습니다.",
            'data' => $this->row($po->fresh('items')),
        ], 201);
    }

    /** GET /api/v1/seller/purchase-orders/{purchaseOrder} */
    public function show(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->authorizeView($request, $purchaseOrder);

        return response()->json(['data' => $this->row($purchaseOrder->load('items', 'supplier'))]);
    }

    /** POST /api/v1/seller/purchase-orders/{purchaseOrder}/receive — 입고 처리 (본사) */
    public function receive(Request $request, PurchaseOrder $purchaseOrder, HqStockService $stock): JsonResponse
    {
        $this->hq($request);
        if (in_array($purchaseOrder->status, ['received', 'canceled'], true)) {
            return response()->json(['message' => '이미 처리된 발주입니다.'], 409);
        }

        DB::transaction(function () use ($purchaseOrder, $stock, $request) {
            foreach ($purchaseOrder->items as $item) {
                if ($item->supply_product_id) {
                    $stock->inbound($item->supply_product_id, $item->product_name, (int) $item->qty,
                        'purchase', 'purchase_order', $purchaseOrder->id, $request->user()->id,
                        "구매발주 {$purchaseOrder->po_no} 입고");
                }
                $item->update(['received_qty' => $item->qty]);
            }
            $purchaseOrder->update(['status' => 'received', 'received_at' => now()]);
        });

        return response()->json([
            'message' => '입고 처리되었습니다. 본사 재고에 반영했습니다.',
            'data' => $this->row($purchaseOrder->fresh('items')),
        ]);
    }

    /** POST /api/v1/seller/purchase-orders/{purchaseOrder}/cancel — 취소 (본사) */
    public function cancel(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        $this->hq($request);
        if ($purchaseOrder->status === 'received') {
            return response()->json(['message' => '입고완료된 발주는 취소할 수 없습니다.'], 409);
        }
        $purchaseOrder->update(['status' => 'canceled']);

        return response()->json([
            'message' => '구매발주를 취소했습니다.',
            'data' => $this->row($purchaseOrder->fresh('items')),
        ]);
    }

    /** POST /api/v1/seller/purchase-orders/{purchaseOrder}/confirm — 공급처 확인 (공급사) */
    public function confirm(Request $request, PurchaseOrder $purchaseOrder): JsonResponse
    {
        [$type, $sid] = $this->seller($request);
        abort_unless($type === 'supplier' && $purchaseOrder->supplier_id == $sid, 403);

        if ($purchaseOrder->status !== 'ordered') {
            return response()->json(['message' => '확인할 수 있는 상태가 아닙니다.'], 409);
        }
        $purchaseOrder->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        $hq = User::where('role', 'hq')->orWhere('is_admin', true)->get();
        app(NotificationService::class)->notifyUsers($hq, 'purchase_order', '✅ 구매발주 확인',
            "«{$purchaseOrder->supplier_name}»이(가) 구매발주 «{$purchaseOrder->po_no}»를 확인했습니다.",
            ['purchase_order_id' => $purchaseOrder->id]);

        return response()->json([
            'message' => '구매발주를 확인했습니다.',
            'data' => $this->row($purchaseOrder->fresh('items')),
        ]);
    }

    private function hq(Request $request): void
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403);
    }

    private function authorizeView(Request $request, PurchaseOrder $po): void
    {
        [$type, $sid] = $this->seller($request);
        abort_unless($type === 'hq' || ($type === 'supplier' && $po->supplier_id == $sid), 403);
    }

    private function generatePoNo(): string
    {
        $date = now()->format('Ymd');
        $seq = PurchaseOrder::whereDate('created_at', today())->count() + 1;

        return sprintf('PU-%s-%03d', $date, $seq);
    }

    private function notifySupplier(int $supplierId, string $title, string $body, array $data): void
    {
        $users = User::where('supplier_id', $supplierId)->where('role', 'supplier')->get();
        app(NotificationService::class)->notifyUsers($users, 'purchase_order', $title, $body, $data);
    }

    private function row(PurchaseOrder $po): array
    {
        return [
            'id' => $po->id,
            'po_no' => $po->po_no,
            'supplier_name' => $po->supplier_name,
            'status' => $po->status,
            'status_label' => PurchaseOrder::STATUSES[$po->status] ?? $po->status,
            'total_amount' => (int) $po->total_amount,
            'item_count' => $po->items->count(),
            'note' => $po->note,
            'created_at' => $po->created_at?->format('Y-m-d H:i'),
            'confirmed_at' => $po->confirmed_at?->format('Y-m-d H:i'),
            'received_at' => $po->received_at?->format('Y-m-d H:i'),
            'items' => $po->items->map(fn (PurchaseOrderItem $it) => [
                'product_name' => $it->product_name,
                'unit' => $it->unit,
                'qty' => (int) $it->qty,
                'unit_price' => (int) $it->unit_price,
                'line_amount' => (int) $it->line_amount,
                'received_qty' => (int) $it->received_qty,
            ])->values(),
        ];
    }
}
