<?php

namespace App\Http\Controllers\Portal\Supplier;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FulfillmentController extends Controller
{
    public function index(Request $request)
    {
        $sid = Auth::user()->supplier_id;
        $status = $request->query('status', 'all');

        $query = OrderItem::forSupplier($sid)->with('order.store')->latest();
        if (array_key_exists($status, OrderItem::FULFILLMENT)) {
            $query->where('fulfillment_status', $status);
        }
        $items = $query->paginate(20)->withQueryString();

        return view('portal.supplier.fulfillment.index', [
            'items' => $items,
            'status' => $status,
            'statuses' => OrderItem::FULFILLMENT,
        ]);
    }

    public function update(Request $request, OrderItem $item)
    {
        $sid = Auth::user()->supplier_id;
        abort_unless($item->supplier_id === $sid && $item->supply_type === 'supplier', 403);

        $data = $request->validate([
            'fulfillment_status' => ['required', 'in:pending,shipping,delivered'],
        ]);

        $item->fulfillment_status = $data['fulfillment_status'];
        $item->shipped_at = in_array($data['fulfillment_status'], ['shipping', 'delivered'], true)
            ? ($item->shipped_at ?? now())
            : null;
        $item->save();

        // 주문 전체 상태 재계산
        $item->order->syncStatus();

        return back()->with('success', '배송 상태가 변경되었습니다. (매장으로 직배송)');
    }
}
