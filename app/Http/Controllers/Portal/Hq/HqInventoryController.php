<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\HqInventory;
use App\Models\HqInventoryMovement;
use App\Models\SupplyProduct;
use App\Services\Inventory\HqStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 물류관리 · 재고관리 — 품목별 본사 재고(실물/예약/가용) 조회 + 수량 입력·수정(실사 보정).
 */
class HqInventoryController extends Controller
{
    public function __construct(private HqStockService $stock)
    {
    }

    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));
        $only = $request->query('only', 'all'); // all | managed | shortage

        $q = SupplyProduct::where('is_active', true)
            ->leftJoin('hq_inventories', 'hq_inventories.supply_product_id', '=', 'supply_products.id')
            ->select('supply_products.id', 'supply_products.name', 'supply_products.code', 'supply_products.unit',
                'supply_products.supply_type',
                'hq_inventories.id as inv_id', 'hq_inventories.qty', 'hq_inventories.reserved_qty')
            ->orderBy('supply_products.name');

        if ($keyword !== '') {
            $q->where(fn ($w) => $w->where('supply_products.name', 'like', "%{$keyword}%")
                ->orWhere('supply_products.code', 'like', "%{$keyword}%"));
        }
        if ($only === 'managed') {
            $q->whereNotNull('hq_inventories.id');
        } elseif ($only === 'shortage') {
            $q->whereNotNull('hq_inventories.id')
                ->whereRaw('hq_inventories.qty - hq_inventories.reserved_qty <= 0');
        }

        $rows = $q->paginate(30)->withQueryString();

        $recent = HqInventoryMovement::latest('id')->limit(15)->get();

        return view('portal.hq.logistics.inventory', compact('rows', 'keyword', 'only', 'recent'));
    }

    /** 실사 수량 입력·수정 (실물 qty를 목표값으로 조정) */
    public function adjust(Request $request)
    {
        $data = $request->validate([
            'supply_product_id' => ['required', 'exists:supply_products,id'],
            'qty' => ['required', 'integer', 'min:0', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $p = SupplyProduct::findOrFail($data['supply_product_id']);
        $this->stock->adjust($p->id, $p->name, (int) $data['qty'], Auth::id(), $data['note'] ?? '실사 조정');

        return back()->with('success', "{$p->name} 재고를 {$data['qty']}{$p->unit}로 조정했습니다.");
    }
}
