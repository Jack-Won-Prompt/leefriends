<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\StoreInventory;
use App\Models\SupplyProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 매장 홈 대시보드 요약.
 */
class StoreDashboardController extends Controller
{
    /**
     * GET /api/v1/store/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        $storeId = $request->user()->store_id;
        abort_unless($storeId, 403, '연결된 매장이 없는 계정입니다.');

        $activeOrders = Order::where('store_id', $storeId)
            ->whereIn('status', ['pending', 'processing', 'shipping'])
            ->count();

        $inTransit = Shipment::where('store_id', $storeId)
            ->where('status', 'confirmed')
            ->count();

        $inventoryItems = StoreInventory::where('store_id', $storeId)->count();
        $lowStock = StoreInventory::where('store_id', $storeId)->where('qty', '<=', 5)->count();

        $monthAmount = (int) Order::where('store_id', $storeId)
            ->where('status', '!=', 'canceled')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('store_amount');

        return response()->json([
            'data' => [
                'active_orders' => $activeOrders,   // 진행중 발주
                'in_transit' => $inTransit,         // 배송중(입고 대기)
                'inventory_items' => $inventoryItems,
                'low_stock' => $lowStock,           // 재고 부족(<=5)
                'month_amount' => $monthAmount,     // 이번 달 매입액
                'new_products' => $this->newProductsToday(), // 당일 신규 품목(홈 상단 배너)
            ],
        ]);
    }

    /**
     * 오늘 등록된 신규 품목 — 매장 노출 가능(활성+승인)한 것만.
     * 대표 품목은 이미지가 있는 것을 우선하고, 그중 가장 최근 등록된 것.
     */
    private function newProductsToday(): array
    {
        $query = SupplyProduct::active()->approved()->whereDate('created_at', today());

        $count = (clone $query)->count();
        $featured = $count > 0
            ? (clone $query)->with('defaultUnit')
                ->orderByRaw("COALESCE(image, '') = ''")
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first()
            : null;

        return [
            'count' => $count,
            'featured' => $featured ? [
                'id' => $featured->id,
                'name' => $featured->name,
                'image' => $featured->image ? asset($featured->image) : null,
                'store_price' => (int) ($featured->defaultUnit?->store_price ?: $featured->store_price),
                'unit' => $featured->defaultUnit?->name ?: $featured->unit,
                'is_market_price' => (bool) $featured->is_market_price,
            ] : null,
        ];
    }
}
