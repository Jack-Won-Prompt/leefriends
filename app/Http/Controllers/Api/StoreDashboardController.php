<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\StoreInventory;
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
            ],
        ]);
    }
}
