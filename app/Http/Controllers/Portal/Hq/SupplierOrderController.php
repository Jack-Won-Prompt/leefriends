<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;

/**
 * 본사 — 공급사 발주 현황 (공급사별 판매주문 모아보기).
 * 매장 발주 중 공급처 직배송분을 본사가 한눈에 조회.
 */
class SupplierOrderController extends Controller
{
    use \App\Support\FiltersByDate;

    public function index(Request $request)
    {
        $supplierId = $request->query('supplier', 'all');
        $status = $request->query('status', 'all');
        [$from, $to] = $this->dateRange($request);

        // 공급사 전체 판매주문 (forSeller 스코프는 특정 공급사용이라 직접 조건 사용)
        $query = SalesOrder::where('seller_type', 'supplier')
            ->with(['supplier', 'store', 'order'])
            ->latest();

        if ($supplierId !== 'all') {
            $query->where('supplier_id', $supplierId);
        }
        if (array_key_exists($status, SalesOrder::STATUSES)) {
            $query->where('status', $status);
        }
        $this->applyDateRange($query, $from, $to);

        // 합계 (필터 반영)
        $sumQuery = (clone $query);

        return view('portal.hq.supplier_orders.index', [
            'salesOrders' => $query->paginate(20)->withQueryString(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'statuses' => SalesOrder::STATUSES,
            'supplierId' => $supplierId,
            'status' => $status,
            'totalSupply' => (int) $sumQuery->sum('supply_amount'),
            'from' => $from,
            'to' => $to,
        ]);
    }
}
