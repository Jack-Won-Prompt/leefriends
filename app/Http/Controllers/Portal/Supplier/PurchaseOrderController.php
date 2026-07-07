<?php

namespace App\Http\Controllers\Portal\Supplier;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** 공급처 — 본사 구매발주 수신 */
class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $sid = Auth::user()->supplier_id;
        abort_unless($sid, 403, '연결된 공급처가 없습니다.');

        $status = $request->query('status', 'all');
        $query = PurchaseOrder::forSupplier($sid)->latest();
        if (array_key_exists($status, PurchaseOrder::STATUSES)) {
            $query->where('status', $status);
        }

        return view('portal.supplier.purchase_orders.index', [
            'orders' => $query->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeOwn($purchaseOrder);
        $purchaseOrder->load('items');

        return view('portal.supplier.purchase_orders.show', ['po' => $purchaseOrder]);
    }

    /** 공급처 발주 확인 */
    public function confirm(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeOwn($purchaseOrder);
        if ($purchaseOrder->status !== 'ordered') {
            return back()->withErrors(['status' => '확인할 수 있는 상태가 아닙니다.']);
        }
        $purchaseOrder->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        // 본사에 확인 알림
        $hq = User::where('role', 'hq')->orWhere('is_admin', true)->get();
        app(\App\Services\Notification\NotificationService::class)->notifyUsers(
            $hq, 'purchase_order', '✅ 구매발주 확인',
            "«{$purchaseOrder->supplier_name}»이(가) 구매발주 «{$purchaseOrder->po_no}»를 확인했습니다.",
            ['purchase_order_id' => $purchaseOrder->id]
        );

        return back()->with('success', '구매발주를 확인했습니다.');
    }

    private function authorizeOwn(PurchaseOrder $po): void
    {
        abort_unless($po->supplier_id === Auth::user()->supplier_id, 403);
    }
}
