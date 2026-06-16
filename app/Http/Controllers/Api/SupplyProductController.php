<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $grouped = SupplyProduct::active()
            ->with(['supplier', 'units'])
            ->catalogOrder()
            ->get()
            ->groupBy('category');

        $groups = $grouped->map(fn ($products, $category) => [
            'category' => $category,
            'category_code' => $products->first()->category_code,
            'products' => $products->map(fn (SupplyProduct $p) => $this->transform($p))->values(),
        ])->values();

        return response()->json([
            'data' => $groups,
            'meta' => ['count' => $grouped->flatten(1)->count()],
        ]);
    }

    private function transform(SupplyProduct $p): array
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
            'image' => $p->image ? asset($p->image) : null,
            'units' => $units,
        ];
    }
}
