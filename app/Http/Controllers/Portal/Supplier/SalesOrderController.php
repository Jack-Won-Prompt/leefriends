<?php

namespace App\Http\Controllers\Portal\Supplier;

use App\Http\Controllers\Controller;
use App\Models\OrderChange;
use App\Models\SalesOrder;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $sid = Auth::user()->supplier_id;
        $status = $request->query('status', 'all');
        $store = $request->query('store', 'all');

        $query = SalesOrder::forSeller('supplier', $sid)->with(['store', 'order', 'items'])->latest();
        if (array_key_exists($status, SalesOrder::STATUSES)) {
            $query->where('status', $status);
        }
        if ($store !== 'all') {
            $query->where('store_id', $store);
        }
        $salesOrders = $query->paginate(15)->withQueryString();

        return view('portal.shared.sales_orders.index', [
            'salesOrders' => $salesOrders,
            'status' => $status,
            'statuses' => SalesOrder::STATUSES,
            'routePrefix' => 'portal.supplier',
            'stores' => Store::whereIn('id', SalesOrder::forSeller('supplier', $sid)->distinct()->pluck('store_id'))->orderBy('name')->get(),
            'store' => $store,
            'asModal' => true, // 상세는 팝업으로
        ]);
    }

    public function confirm(SalesOrder $salesOrder)
    {
        abort_unless($salesOrder->seller_type === 'supplier' && $salesOrder->supplier_id === Auth::user()->supplier_id, 403);
        abort_unless($salesOrder->status === 'created', 400, '이미 확인된 판매주문입니다.');

        if (OrderChange::forSeller('supplier', Auth::user()->supplier_id)->pending()->where('order_id', $salesOrder->order_id)->exists()) {
            return back()->with('error', '해당 주문에 미반영된 매장 변경이 있습니다. «매장 주문 변경»에서 확인(반영) 후 진행하세요.');
        }

        $salesOrder->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        return back()->with('success', '판매주문을 확인했습니다. 매장에 입고예정 정보가 생성되었습니다.');
    }
}
