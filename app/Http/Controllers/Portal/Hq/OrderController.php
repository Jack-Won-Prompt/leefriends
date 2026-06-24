<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $store = $request->query('store', 'all');
        $tax = $request->query('tax', 'all');

        $query = Order::with('store')->withCount('items')->latest();
        if (array_key_exists($status, Order::STATUSES)) {
            $query->where('status', $status);
        }
        if ($store !== 'all') {
            $query->where('store_id', $store);
        }
        if ($tax === 'issued') {
            $query->whereNotNull('tax_invoice_id');
        } elseif ($tax === 'pending') {
            $query->whereNull('tax_invoice_id');
        }
        $orders = $query->paginate(15)->withQueryString();

        return view('portal.hq.orders.index', [
            'orders' => $orders,
            'status' => $status,
            'statuses' => Order::STATUSES,
            'stores' => Store::orderBy('name')->get(),
            'store' => $store,
            'tax' => $tax,
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items.supplier', 'store', 'user']);

        return view('portal.hq.orders.show', compact('order'));
    }

    /** 본사 직공급 품목의 배송상태 처리 */
    public function updateItem(Request $request, Order $order, OrderItem $item)
    {
        abort_unless($item->order_id === $order->id && $item->supply_type === 'hq', 403);

        $data = $request->validate([
            'fulfillment_status' => ['required', 'in:pending,shipping,delivered'],
        ]);

        $item->fulfillment_status = $data['fulfillment_status'];
        $item->shipped_at = in_array($data['fulfillment_status'], ['shipping', 'delivered'], true)
            ? ($item->shipped_at ?? now())
            : null;
        $item->save();

        $order->syncStatus();

        return back()->with('success', '본사 공급 품목의 배송상태가 변경되었습니다.');
    }
}
