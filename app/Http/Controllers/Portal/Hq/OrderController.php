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

    /** 택배비(박스·단가) 추가/수정 → 발주 합계에 반영 */
    public function updateShipping(Request $request, Order $order)
    {
        $data = $request->validate([
            'shipping_box_count' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'shipping_unit_price' => ['nullable', 'integer', 'min:0', 'max:9999999'],
        ]);

        $box = (int) ($data['shipping_box_count'] ?? 0);
        $unit = (int) ($data['shipping_unit_price'] ?? 0);

        $order->update([
            'shipping_box_count' => $box ?: null,
            'shipping_unit_price' => $unit ?: null,
            'shipping_fee' => $box * $unit,
        ]);

        return back()->with('success', '택배비를 저장했습니다. (발주 합계에 반영)');
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

    /** 싯가 품목 단가 확정 */
    public function setItemPrice(Request $request, Order $order, OrderItem $item, \App\Services\Notification\NotificationService $notifications)
    {
        abort_unless($item->order_id === $order->id, 403);
        abort_unless($item->price_pending, 422, '단가 확정 대기 중인 품목이 아닙니다.');

        $data = $request->validate([
            'store_unit_price' => ['required', 'integer', 'min:1', 'max:100000000'],
        ], ['store_unit_price.required' => '단가를 입력해 주세요.']);

        $price = (int) $data['store_unit_price'];
        $item->update([
            'store_unit_price' => $price,
            'store_line_amount' => $price * $item->qty,
            'price_pending' => false,
        ]);

        $order->recomputeAmounts();

        try {
            $notifications->notifyStore(
                (int) $order->store_id,
                'market_price_set',
                '🥭 싯가 가격 확정',
                "{$item->product_name} 단가가 ".number_format($price).'원으로 확정되었습니다.',
                ['order_id' => $order->id],
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', "«{$item->product_name}» 단가를 ".number_format($price).'원으로 확정했습니다.');
    }
}
