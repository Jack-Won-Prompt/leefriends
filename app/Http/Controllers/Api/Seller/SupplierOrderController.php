<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\SalesOrder;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 본사 — 공급사 발주 현황 (공급사별 판매주문 모아보기).
 * 매장 발주 중 공급처 직배송분을 본사가 한눈에 조회/필터.
 */
class SupplierOrderController extends Controller
{
    use ResolvesSeller;

    /** GET /seller/supplier-orders?supplier=all|{id}&status=all|created|... */
    public function index(Request $request): JsonResponse
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');

        $supplierId = $request->query('supplier', 'all');
        $status = $request->query('status', 'all');

        $query = SalesOrder::where('seller_type', 'supplier')
            ->with(['supplier', 'store', 'order'])
            ->latest();

        if ($supplierId !== 'all') {
            $query->where('supplier_id', $supplierId);
        }
        if (array_key_exists($status, SalesOrder::STATUSES)) {
            $query->where('status', $status);
        }

        $totalSupply = (int) (clone $query)->sum('supply_amount');
        $page = $query->paginate(20);

        return response()->json([
            'data' => $page->getCollection()->map(fn (SalesOrder $so) => $this->summary($so))->values(),
            'meta' => [
                'supplier' => $supplierId,
                'status' => $status,
                'total_supply' => $totalSupply,
                'count' => $page->total(),
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
                'suppliers' => Supplier::orderBy('name')->get(['id', 'name'])
                    ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
                'statuses' => collect(SalesOrder::STATUSES)
                    ->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            ],
        ]);
    }

    /** GET /seller/supplier-orders/{salesOrder} — 상세 (본사) */
    public function show(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');
        abort_unless($salesOrder->seller_type === 'supplier', 404);

        $salesOrder->load(['supplier', 'store', 'order', 'items']);

        return response()->json(['data' => array_merge($this->summary($salesOrder), [
            'items' => $salesOrder->items->map(fn (OrderItem $it) => [
                'id' => $it->id,
                'product_name' => $it->product_name,
                'unit' => $it->unit,
                'qty' => (int) $it->qty,
                'supply_unit_price' => (int) $it->supply_unit_price,
                'supply_line_amount' => (int) $it->supply_line_amount,
                'store_line_amount' => (int) $it->store_line_amount,
                'fulfillment_status' => $it->fulfillment_status,
            ])->values(),
        ])]);
    }

    private function summary(SalesOrder $so): array
    {
        return [
            'id' => $so->id,
            'sales_order_no' => $so->sales_order_no,
            'status' => $so->status,
            'status_label' => SalesOrder::STATUSES[$so->status] ?? $so->status,
            'supplier_name' => $so->supplier?->name ?? '공급처',
            'store_name' => $so->store?->name,
            'order_no' => $so->order?->order_no,
            'item_count' => (int) $so->item_count,
            'store_amount' => (int) $so->store_amount,
            'supply_amount' => (int) $so->supply_amount,
            'confirmed_at' => $so->confirmed_at?->format('Y-m-d H:i'),
            'created_at' => $so->created_at?->format('Y-m-d H:i'),
        ];
    }
}
