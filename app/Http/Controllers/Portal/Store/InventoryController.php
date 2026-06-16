<?php

namespace App\Http\Controllers\Portal\Store;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\StoreInventory;
use App\Models\SupplyProduct;
use App\Services\Inventory\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $storeId = Auth::user()->store_id;
        $keyword = trim((string) $request->query('q', ''));

        $query = StoreInventory::where('store_id', $storeId)->orderByDesc('qty');
        if ($keyword !== '') {
            $query->where('product_name', 'like', "%{$keyword}%");
        }
        $inventories = $query->get();

        return view('portal.store.inventory.index', compact('inventories', 'keyword'));
    }

    public function movements(Request $request)
    {
        $storeId = Auth::user()->store_id;
        $type = $request->query('type', 'all');

        $query = InventoryMovement::where('store_id', $storeId)->latest();
        if (in_array($type, ['in', 'out', 'adjust'], true)) {
            $query->where('type', $type);
        }
        $movements = $query->paginate(20)->withQueryString();

        return view('portal.store.inventory.movements', [
            'movements' => $movements,
            'type' => $type,
            'types' => InventoryMovement::TYPES,
        ]);
    }

    /** 재고 사용(출고): 바코드 스캔 또는 재고 선택 → 차감 */
    public function usage(Request $request, InventoryService $inventory)
    {
        $storeId = Auth::user()->store_id;

        $data = $request->validate([
            'inventory_id' => ['nullable', 'integer'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'qty' => ['required', 'integer', 'min:1', 'max:99999'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        // 바코드 우선: 제품 바코드로 해당 매장 재고 탐색
        $inv = null;
        if (! empty($data['inventory_id'])) {
            $inv = StoreInventory::where('store_id', $storeId)->find($data['inventory_id']);
        } elseif (! empty($data['barcode'])) {
            $product = SupplyProduct::where('barcode', $data['barcode'])->first();
            abort_if(! $product, 404, '바코드에 해당하는 제품을 찾을 수 없습니다.');
            $inv = StoreInventory::where('store_id', $storeId)
                ->where('supply_product_id', $product->id)
                ->orderByDesc('qty')->first();
        }

        abort_if(! $inv, 404, '재고 항목을 찾을 수 없습니다.');

        $inventory->useStock($storeId, $inv->supply_product_id, $inv->supply_product_unit_id, (int) $data['qty'], Auth::id(), $data['note'] ?? null);

        return back()->with('success', "{$inv->product_name} {$data['qty']}{$inv->unit_name} 출고 처리되었습니다.");
    }
}
