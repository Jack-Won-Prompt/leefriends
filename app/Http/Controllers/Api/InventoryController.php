<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\StoreInventory;
use App\Models\SupplyProduct;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 매장 재고 — 현황 / 이동내역 / 사용(소진) 등록.
 */
class InventoryController extends Controller
{
    /**
     * GET /api/v1/inventory?q=
     */
    public function index(Request $request): JsonResponse
    {
        $storeId = $this->storeId($request);
        $keyword = trim((string) $request->query('q', ''));

        $query = StoreInventory::with('supplyProduct')->where('store_id', $storeId)->orderByDesc('qty');
        if ($keyword !== '') {
            $query->where('product_name', 'like', "%{$keyword}%");
        }

        $items = $query->get()->map(fn (StoreInventory $inv) => [
            'id' => $inv->id,
            'product_id' => $inv->supply_product_id,
            'unit_id' => $inv->supply_product_unit_id,
            'product_name' => $inv->product_name,
            'image' => $inv->supplyProduct?->image ? asset($inv->supplyProduct->image) : null,
            'unit_name' => $inv->unit_name,
            'qty' => (int) $inv->qty,
        ]);

        return response()->json(['data' => $items->values()]);
    }

    /**
     * GET /api/v1/inventory/movements?type=all|in|out|adjust
     */
    public function movements(Request $request): JsonResponse
    {
        $storeId = $this->storeId($request);
        $type = $request->query('type', 'all');

        $query = InventoryMovement::where('store_id', $storeId)->latest();
        if (in_array($type, ['in', 'out', 'adjust'], true)) {
            $query->where('type', $type);
        }
        $movements = $query->paginate(20);

        return response()->json([
            'data' => $movements->getCollection()->map(fn (InventoryMovement $m) => [
                'id' => $m->id,
                'type' => $m->type,
                'type_label' => InventoryMovement::TYPES[$m->type] ?? $m->type,
                'product_name' => $m->product_name,
                'unit_name' => $m->unit_name,
                'qty' => (int) $m->qty,
                'balance_after' => (int) $m->balance_after,
                'note' => $m->note,
                'created_at' => $m->created_at?->format('Y-m-d H:i'),
            ])->values(),
            'meta' => [
                'type' => $type,
                'current_page' => $movements->currentPage(),
                'last_page' => $movements->lastPage(),
                'total' => $movements->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/inventory/usage
     * body: { inventory_id?, barcode?, qty, note? }  (재고선택 또는 바코드)
     */
    public function usage(Request $request, InventoryService $inventory): JsonResponse
    {
        $storeId = $this->storeId($request);

        $data = $request->validate([
            'inventory_id' => ['nullable', 'integer'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'qty' => ['required', 'integer', 'min:1', 'max:99999'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $inv = null;
        if (! empty($data['inventory_id'])) {
            $inv = StoreInventory::where('store_id', $storeId)->find($data['inventory_id']);
        } elseif (! empty($data['barcode'])) {
            $product = SupplyProduct::where('barcode', $data['barcode'])->first();
            if (! $product) {
                return response()->json(['message' => '바코드에 해당하는 제품을 찾을 수 없습니다.'], 404);
            }
            $inv = StoreInventory::where('store_id', $storeId)
                ->where('supply_product_id', $product->id)
                ->orderByDesc('qty')->first();
        }

        if (! $inv) {
            return response()->json(['message' => '재고 항목을 찾을 수 없습니다.'], 404);
        }

        $inventory->useStock(
            $storeId,
            $inv->supply_product_id,
            $inv->supply_product_unit_id,
            (int) $data['qty'],
            $request->user()->id,
            $data['note'] ?? null,
        );

        return response()->json([
            'message' => "{$inv->product_name} {$data['qty']}{$inv->unit_name} 출고 처리되었습니다.",
        ]);
    }

    private function storeId(Request $request): int
    {
        $id = $request->user()->store_id;
        abort_unless($id, 403, '연결된 매장이 없는 계정입니다.');

        return $id;
    }
}
