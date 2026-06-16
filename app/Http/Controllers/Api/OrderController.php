<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SupplyProduct;
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

        $orders = Order::where('store_id', $storeId)
            ->withCount('items')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $orders->getCollection()->map(fn (Order $o) => $this->summary($o))->values(),
            'meta' => [
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
     * POST /api/v1/orders  — 발주 접수
     * body: { note?, items: [{ product_id, unit_id?, qty }] }
     */
    public function store(Request $request): JsonResponse
    {
        $storeId = $this->storeId($request);

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.unit_id' => ['nullable', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $lines = collect($data['items'])->filter(fn ($i) => (int) $i['qty'] > 0);
        if ($lines->isEmpty()) {
            return response()->json(['message' => '발주할 품목의 수량을 입력해 주세요.'], 422);
        }

        $user = $request->user();

        $order = DB::transaction(function () use ($user, $storeId, $lines, $data) {
            $order = Order::create([
                'order_no' => $this->generateOrderNo(),
                'store_id' => $storeId,
                'user_id' => $user->id,
                'status' => 'pending',
                'note' => $data['note'] ?? null,
            ]);
            $this->buildItems($order, $lines);
            (new SalesOrderGenerator())->generate($order);

            return $order;
        });

        $order->load('items');

        return response()->json([
            'message' => '발주가 접수되었습니다.',
            'data' => $this->detail($order),
        ], 201);
    }

    /* ----------------- helpers ----------------- */

    private function buildItems(Order $order, $lines): void
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

            $storePrice = $unit->store_price ?? $p->store_price;
            $supplyPrice = $p->supply_type === 'supplier' ? ($unit->supply_price ?? $p->supply_price) : 0;
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

    private function generateOrderNo(): string
    {
        $date = now()->format('Ymd');
        $seq = Order::whereDate('created_at', today())->count() + 1;

        return sprintf('PO-%s-%03d', $date, $seq);
    }

    private function summary(Order $o): array
    {
        return [
            'id' => $o->id,
            'order_no' => $o->order_no,
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
            'items' => $o->items->map(fn (OrderItem $it) => [
                'id' => $it->id,
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
