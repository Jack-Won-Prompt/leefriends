<?php

namespace App\Http\Controllers\Portal\Supplier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    use \App\Support\FiltersByDate;

    public function index(Request $request)
    {
        $sid = Auth::user()->supplier_id;
        $store = $request->query('store', 'all');
        [$from, $to] = $this->dateRange($request);

        // 자사(공급처) 품목이 포함된 주문만
        $mine = fn ($q) => $q->where('supplier_id', $sid)->where('supply_type', 'supplier');

        $query = Order::whereHas('items', $mine)->with(['store', 'items' => $mine])->latest();
        if ($store !== 'all') {
            $query->where('store_id', $store);
        }
        $this->applyDateRange($query, $from, $to);
        $orders = $query->paginate(15)->withQueryString();

        $storeIds = Order::whereHas('items', $mine)->distinct()->pluck('store_id');

        return view('portal.supplier.orders.index', [
            'orders' => $orders,
            'stores' => Store::whereIn('id', $storeIds)->orderBy('name')->get(),
            'store' => $store,
            'from' => $from,
            'to' => $to,
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

    /** 자사 공급 품목의 배송상태(배송중/배송완료) 변경 */
    public function updateItem(Request $request, OrderItem $item)
    {
        $sid = Auth::user()->supplier_id;
        abort_unless($item->supply_type === 'supplier' && (int) $item->supplier_id === (int) $sid, 403);

        $data = $request->validate([
            'fulfillment_status' => ['required', 'in:pending,shipping,delivered'],
        ]);

        $item->fulfillment_status = $data['fulfillment_status'];
        $item->shipped_at = in_array($data['fulfillment_status'], ['shipping', 'delivered'], true)
            ? ($item->shipped_at ?? now())
            : null;
        $item->save();

        $item->order?->syncStatus();

        return back()->with('success', '공급 품목의 배송상태가 변경되었습니다.');
    }
}
