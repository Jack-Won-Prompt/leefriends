<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SalesOrder;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 본사/공급처 대시보드 요약 (처리 대기 카운트).
 */
class DashboardController extends Controller
{
    use ResolvesSeller;

    /**
     * GET /api/v1/seller/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        $pendingSalesOrders = SalesOrder::forSeller($type, $sid)->where('status', 'created')->count();
        $confirmedSalesOrders = SalesOrder::forSeller($type, $sid)->where('status', 'confirmed')->count();
        $shipmentsToConfirm = Shipment::forSeller($type, $sid)->where('status', 'created')->count();
        $inTransit = Shipment::forSeller($type, $sid)->where('status', 'confirmed')->count();
        $pendingChanges = \App\Models\OrderChange::forSeller($type, $sid)->pending()->count();

        // 오늘 받은 발주 + 최근 발주 (본사: 전체 / 공급처: 자사 품목 포함)
        if ($type === 'supplier') {
            $mine = fn ($q) => $q->where('supplier_id', $sid)->where('supply_type', 'supplier');
            $ordersQuery = fn () => Order::whereHas('items', $mine);
        } else {
            $ordersQuery = fn () => Order::query();
        }

        $todayOrders = $ordersQuery()->whereDate('created_at', today())->count();

        $recent = $ordersQuery()
            ->with('store')->withCount('items')->latest()->limit(5)->get()
            ->map(fn (Order $o) => [
                'id' => $o->id,
                'order_no' => $o->order_no,
                'status' => $o->status,
                'status_label' => Order::STATUSES[$o->status] ?? $o->status,
                'store_name' => $o->store?->name,
                'item_count' => $o->items_count,
                'store_amount' => (int) $o->store_amount,
                'created_at' => $o->created_at?->format('Y-m-d H:i'),
            ]);

        return response()->json([
            'data' => [
                'role' => $type,
                'pending_sales_orders' => $pendingSalesOrders,   // 확인 대기
                'confirmed_sales_orders' => $confirmedSalesOrders, // 출고 대기
                'shipments_to_confirm' => $shipmentsToConfirm,   // 송장 입력 대기
                'in_transit' => $inTransit,                       // 배송중
                'pending_changes' => $pendingChanges,             // 미반영 주문변경
                'today_orders' => $todayOrders,
                'recent_orders' => $recent,
            ],
        ]);
    }
}
