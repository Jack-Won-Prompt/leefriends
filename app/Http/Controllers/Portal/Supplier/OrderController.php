<?php

namespace App\Http\Controllers\Portal\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $sid = Auth::user()->supplier_id;
        $store = $request->query('store', 'all');

        // 자사(공급처) 품목이 포함된 주문만
        $mine = fn ($q) => $q->where('supplier_id', $sid)->where('supply_type', 'supplier');

        $query = Order::whereHas('items', $mine)->with(['store', 'items' => $mine])->latest();
        if ($store !== 'all') {
            $query->where('store_id', $store);
        }
        $orders = $query->paginate(15)->withQueryString();

        $storeIds = Order::whereHas('items', $mine)->distinct()->pluck('store_id');

        return view('portal.supplier.orders.index', [
            'orders' => $orders,
            'stores' => Store::whereIn('id', $storeIds)->orderBy('name')->get(),
            'store' => $store,
        ]);
    }

    public function show(Order $order)
    {
        $sid = Auth::user()->supplier_id;
        $mine = fn ($q) => $q->where('supplier_id', $sid)->where('supply_type', 'supplier');

        // 자사 품목이 없는 주문은 접근 불가
        abort_unless($order->items()->where($mine)->exists(), 403);

        $order->load(['store', 'user', 'items' => $mine]);

        return view('portal.supplier.orders.show', compact('order'));
    }
}
