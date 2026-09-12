<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HqInventory;
use App\Models\SupplyProduct;
use Illuminate\Http\JsonResponse;

class SupplyProductController extends Controller
{
    /**
     * GET /api/v1/supply-products
     * 매장 발주용 물품 카탈로그. 대분류(category)별 그룹 + 단위/출고가 포함.
     */
    public function index(): JsonResponse
    {
        $all = SupplyProduct::active()
            ->with(['supplier', 'units'])
            ->catalogOrder()
            ->get();

        // 본사 가용재고 — 웹 매장 발주 화면과 같은 기준(재고 레코드 없거나 가용 ≤ 0 → 재고 없음)
        $inv = HqInventory::whereIn('supply_product_id', $all->pluck('id'))->get()->keyBy('supply_product_id');

        $grouped = $all->groupBy('category');

        $groups = $grouped->map(fn ($products, $category) => [
            'category' => $category,
            'category_code' => $products->first()->category_code,
            // 신규 상품(등록순 최신)을 앞으로, 나머지는 기존 카탈로그 순서 유지
            'products' => $products
                ->sortByDesc(fn (SupplyProduct $p) => $p->is_new ? $p->created_at->getTimestamp() : 0)
                ->map(fn (SupplyProduct $p) => $this->transform($p, $inv->get($p->id)))->values(),
        ])->values();

        return response()->json([
            'data' => $groups,
            'meta' => ['count' => $grouped->flatten(1)->count()],
        ]);
    }

    private function transform(SupplyProduct $p, ?HqInventory $inv = null): array
    {
        $units = $p->units->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'store_price' => (int) ($u->store_price ?: $p->store_price),
            'is_default' => (bool) $u->is_default,
        ])->values();

        // 단위가 하나도 없으면 제품 자체를 기본 단위로 노출
        if ($units->isEmpty()) {
            $units = collect([[
                'id' => null,
                'name' => $p->unit,
                'store_price' => (int) $p->store_price,
                'is_default' => true,
            ]]);
        }

        return [
            'id' => $p->id,
            'code' => $p->code,
            'name' => $p->name,
            'category' => $p->category,
            'category_code' => $p->category_code,
            'spec' => $p->spec,
            'unit' => $p->unit,
            'supply_type' => $p->supply_type,
            'supply_type_label' => $p->supply_type_label,
            'supplier_name' => $p->supply_type === 'supplier' ? $p->supplier?->name : '본사',
            'store_price' => (int) $p->store_price,
            'is_market_price' => (bool) $p->is_market_price,
            'is_new' => (bool) $p->is_new,
            'image' => $p->image ? asset($p->image) : null,
            'units' => $units,
            // 재고 없음이면 발주 불가 (접수 시 서버 reserveOrder 도 같은 기준으로 차단)
            'stock_available' => $inv?->available,
            'out_of_stock' => ! $inv || $inv->available <= 0,
        ];
    }
}
