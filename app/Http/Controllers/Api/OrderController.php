<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SalesOrder;
use App\Models\SupplyProduct;
use App\Services\Fulfillment\OrderChangeService;
use App\Services\Fulfillment\SalesOrderGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 매장 발주 API. 웹 포털 Store\OrderController 와 동일한 발주 생성 규칙
 * (품목코드/판매주문 생성/공급처 라우팅)을 재사용합니다.
 */
class OrderController extends Controller
{
    /**
     * GET /api/v1/orders  — 내 매장 발주 목록
     */
    public function index(Request $request): JsonResponse
    {
        $storeId = $this->storeId($request);
        $type = $request->query('type', 'all'); // all | normal | sample

        $query = Order::where('store_id', $storeId)->withCount('items')->latest();
        if (in_array($type, ['normal', 'sample'], true)) {
            $query->where('order_type', $type);
        }
        $orders = $query->paginate(20);

        return response()->json([
            'data' => $orders->getCollection()->map(fn (Order $o) => $this->summary($o))->values(),
            'meta' => [
                'type' => $type,
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/orders/{order}  — 발주 상세
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizeStore($request, $order);
        $order->load('items');

        return response()->json(['data' => $this->detail($order)]);
    }

    /**
     * GET /api/v1/orders/{order}/statement  — 발주 거래명세서 (공급자별 그룹)
     */
    public function statement(Request $request, Order $order): JsonResponse
    {
        $this->authorizeStore($request, $order);
        if (($order->order_type ?? 'normal') === 'sample') {
            return response()->json(['message' => '샘플 주문은 거래명세서를 제공하지 않습니다.'], 404);
        }
        $order->load(['items', 'store']);

        $groups = $order->items
            ->groupBy(fn ($it) => $it->supply_type === 'supplier' ? 'supplier:'.$it->supplier_id : 'hq')
            ->map(function ($items) {
                $first = $items->first();
                $seller = $first->supply_type === 'supplier' ? ($first->supplier_name ?? '공급처') : '본사';

                return [
                    'seller' => $seller,
                    'subtotal' => (int) $items->sum('store_line_amount'),
                    'items' => $items->map(fn ($it) => [
                        'name' => $it->product_name,
                        'unit' => $it->unit,
                        'qty' => (int) $it->qty,
                        'unit_price' => (int) $it->store_unit_price,
                        'amount' => (int) $it->store_line_amount,
                    ])->values(),
                ];
            })->values();

        $total = (int) $order->items->sum('store_line_amount');

        return response()->json([
            'data' => [
                'order_no' => $order->order_no,
                'created_at' => $order->created_at?->format('Y-m-d'),
                'store_name' => $order->store?->name,
                'store_address' => trim((string) ($order->store?->address ?? '')),
                'groups' => $groups,
                'total' => $total,
            ],
        ]);
    }

    /**
     * POST /api/v1/orders  — 발주 접수
     * body: { note?, items: [{ product_id, unit_id?, qty }] }
     */
    public function store(Request $request, \App\Services\Notification\NotificationService $notifications): JsonResponse
    {
        $storeId = $this->storeId($request);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
            'order_type' => ['nullable', 'in:normal,sample'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.unit_id' => ['nullable', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);
        $type = ($data['order_type'] ?? 'normal') === 'sample' ? 'sample' : 'normal';

        $lines = $this->validLines($data['items']);
        if ($lines->isEmpty()) {
            return response()->json(['message' => '유효한 품목이 없습니다. 품목 정보를 다시 확인해 주세요.'], 422);
        }

        $user = $request->user();

        $order = DB::transaction(function () use ($user, $storeId, $lines, $data, $type) {
            $order = Order::create([
                'order_no' => $this->generateOrderNo($type),
                'store_id' => $storeId,
                'user_id' => $user->id,
                'status' => 'pending',
                'order_type' => $type,
                'note' => $data['note'] ?? null,
            ]);
            $this->buildItems($order, $lines, $type === 'sample');
            (new SalesOrderGenerator())->generate($order);

            return $order;
        });

        $order->load('items');

        // 본사 + 해당 공급처에 새 발주 알림(FCM)
        $notifications->notifyNewOrder($order);

        return response()->json([
            'message' => '발주가 접수되었습니다.',
            'data' => $this->detail($order),
        ], 201);
    }

    /**
     * PUT /api/v1/orders/{order}  — 발주 수정 (출고 전에만 가능)
     * body: { note?, items: [{ product_id, unit_id?, qty }] }
     */
    public function update(Request $request, Order $order, OrderChangeService $changes): JsonResponse
    {
        $this->authorizeStore($request, $order);
        if (! $this->isEditable($order)) {
            return response()->json(
                ['message' => '이미 출고가 진행되었거나 취소/완료된 발주는 수정할 수 없습니다.'],
                409,
            );
        }

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.unit_id' => ['nullable', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $lines = $this->validLines($data['items']);
        if ($lines->isEmpty()) {
            return response()->json(['message' => '유효한 품목이 없습니다. 품목 정보를 다시 확인해 주세요.'], 422);
        }

        $oldItems = $order->items()->get();

        DB::transaction(function () use ($order, $lines, $data) {
            // 기존 판매주문/품목 제거 후 재생성 (미출고 상태에서만 허용)
            SalesOrder::where('order_id', $order->id)->delete();
            $order->items()->delete();
            $order->update(['status' => 'pending', 'note' => $data['note'] ?? null]);

            $this->buildItems($order, $lines);
            (new SalesOrderGenerator())->generate($order);
        });

        // 본사·공급처에 변경 알림 + 미반영 기록
        $changes->record($order, 'updated', $oldItems);

        $order->load('items');

        return response()->json([
            'message' => '발주가 수정되었습니다. 본사·공급처에 변경 알림이 전송되었습니다.',
            'data' => $this->detail($order),
        ]);
    }

    /**
     * DELETE /api/v1/orders/{order}  — 발주 취소 (출고 전에만 가능)
     */
    public function destroy(Request $request, Order $order, OrderChangeService $changes): JsonResponse
    {
        $this->authorizeStore($request, $order);
        if (! $this->isEditable($order)) {
            return response()->json(
                ['message' => '이미 출고가 진행되었거나 취소/완료된 발주는 취소할 수 없습니다.'],
                409,
            );
        }

        $snapshot = $order->items()->get();

        DB::transaction(function () use ($order) {
            SalesOrder::where('order_id', $order->id)->update(['status' => 'canceled']);
            $order->update(['status' => 'canceled']);
        });

        $changes->record($order, 'canceled', $snapshot);

        $order->load('items');

        return response()->json([
            'message' => '발주가 취소되었습니다. 본사·공급처에 취소 알림이 전송되었습니다.',
            'data' => $this->detail($order),
        ]);
    }

    /* ----------------- helpers ----------------- */

    /** 수정/취소 가능: 미취소·미완료 + 어떤 품목도 출고에 묶이지 않음 */
    private function isEditable(Order $order): bool
    {
        return ! in_array($order->status, ['canceled', 'completed'], true)
            && ! $order->items()->whereNotNull('shipment_id')->exists();
    }

    /** 요청 items 중 활성 품목(qty>0)만 남긴 컬렉션 */
    private function validLines(array $items)
    {
        $lines = collect($items)->filter(fn ($i) => (int) $i['qty'] > 0);
        $validIds = SupplyProduct::active()
            ->whereIn('id', $lines->pluck('product_id')->all())
            ->pluck('id')
            ->all();

        return $lines
            ->filter(fn ($i) => in_array((int) $i['product_id'], $validIds, true))
            ->values();
    }

    private function buildItems(Order $order, $lines, bool $isSample = false): void
    {
        $ids = $lines->pluck('product_id')->all();
        $products = SupplyProduct::active()->whereIn('id', $ids)
            ->with(['supplier', 'units'])->get()->keyBy('id');

        $storeTotal = 0;
        $supplyTotal = 0;

        foreach ($lines as $line) {
            $p = $products[$line['product_id']] ?? null;
            if (! $p) {
                continue;
            }
            $qty = (int) $line['qty'];
            $unitId = isset($line['unit_id']) ? (int) $line['unit_id'] : 0;
            $unit = $p->units->firstWhere('id', $unitId)
                ?? $p->units->firstWhere('is_default', true)
                ?? $p->units->first();

            // 샘플 주문은 단가·금액 0 처리
            $storePrice = $isSample ? 0 : ($unit->store_price ?? $p->store_price);
            $supplyPrice = $isSample ? 0 : ($p->supply_type === 'supplier' ? ($unit->supply_price ?? $p->supply_price) : 0);
            $storeLine = $storePrice * $qty;
            $supplyLine = $supplyPrice * $qty;

            OrderItem::create([
                'order_id' => $order->id,
                'supply_product_id' => $p->id,
                'supply_product_unit_id' => $unit->id ?? null,
                'product_name' => $p->name,
                'unit' => $unit->name ?? $p->unit,
                'supply_type' => $p->supply_type,
                'supplier_id' => $p->supply_type === 'supplier' ? $p->supplier_id : null,
                'supplier_name' => $p->supply_type === 'supplier' ? optional($p->supplier)->name : '본사',
                'qty' => $qty,
                'store_unit_price' => $storePrice,
                'supply_unit_price' => $supplyPrice,
                'store_line_amount' => $storeLine,
                'supply_line_amount' => $supplyLine,
                'fulfillment_status' => 'pending',
            ]);

            $storeTotal += $storeLine;
            $supplyTotal += $supplyLine;
        }

        $order->update(['store_amount' => $storeTotal, 'supply_amount' => $supplyTotal]);
    }

    private function generateOrderNo(string $type = 'normal'): string
    {
        $date = now()->format('Ymd');
        $prefix = $type === 'sample' ? 'SP' : 'PO';
        $seq = Order::whereDate('created_at', today())->count() + 1;

        return sprintf('%s-%s-%03d', $prefix, $date, $seq);
    }

    private function summary(Order $o): array
    {
        return [
            'id' => $o->id,
            'order_no' => $o->order_no,
            'order_type' => $o->order_type ?? 'normal',
            'is_sample' => ($o->order_type ?? 'normal') === 'sample',
            'status' => $o->status,
            'status_label' => Order::STATUSES[$o->status] ?? $o->status,
            'item_count' => $o->items_count ?? $o->items()->count(),
            'store_amount' => (int) $o->store_amount,
            'created_at' => $o->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function detail(Order $o): array
    {
        return array_merge($this->summary($o), [
            'note' => $o->note,
            'editable' => $this->isEditable($o),
            'items' => $o->items->map(fn (OrderItem $it) => [
                'id' => $it->id,
                'product_id' => $it->supply_product_id,
                'unit_id' => $it->supply_product_unit_id,
                'product_name' => $it->product_name,
                'unit' => $it->unit,
                'qty' => (int) $it->qty,
                'store_unit_price' => (int) $it->store_unit_price,
                'store_line_amount' => (int) $it->store_line_amount,
                'supplier_name' => $it->supplier_name,
            ])->values(),
        ]);
    }

    private function storeId(Request $request): int
    {
        $id = $request->user()->store_id;
        abort_unless($id, 403, '연결된 매장이 없는 계정입니다. 매장 계정으로 로그인해 주세요.');

        return $id;
    }

    private function authorizeStore(Request $request, Order $order): void
    {
        abort_unless($order->store_id === $request->user()->store_id, 403);
    }
}
