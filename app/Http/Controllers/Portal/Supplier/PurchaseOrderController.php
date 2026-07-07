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
        $query = PurchaseOrder::forSupplier($sid)->with('items')->latest();
        if (array_key_exists($status, PurchaseOrder::STATUSES)) {
            $query->where('status', $status);
        }

        return view('portal.supplier.purchase_orders.index', [
            'orders' => $query->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    /** 구매 거래명세서 PDF 미리보기 (공급처 발행 문서) */
    public function statementPdf(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeOwn($purchaseOrder);
        $purchaseOrder->load(['items.supplyProduct', 'supplier']);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.print.purchase-order-statement-pdf', ['po' => $purchaseOrder])
            ->setPaper('a4')->stream(\App\Support\StatementFile::purchaseName($purchaseOrder->supplier_name, $purchaseOrder->created_at, $this->poSeq($purchaseOrder)));
    }

    /** 공급처별 그 날짜의 구매발주 순번 */
    private function poSeq(PurchaseOrder $po): int
    {
        return max(1, PurchaseOrder::where('supplier_id', $po->supplier_id)
            ->whereDate('created_at', $po->created_at)->where('id', '<=', $po->id)->count());
    }

    /** 공급처 → 본사 거래명세서 발행 (본사 확인용) */
    public function issueStatement(PurchaseOrder $purchaseOrder)
    {
        $this->authorizeOwn($purchaseOrder);
        $purchaseOrder->load(['items.supplyProduct', 'supplier']);
        $purchaseOrder->update(['statement_issued_at' => now()]);

        // 본사에 이메일(있으면) + 인앱 알림
        $hqEmail = config('services.company.email');
        if ($hqEmail) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.print.purchase-order-statement-pdf', ['po' => $purchaseOrder])->setPaper('a4');
            \Illuminate\Support\Facades\Mail::to($hqEmail)->send(
                new \App\Mail\PurchaseStatementMail($purchaseOrder, $pdf->output(), \App\Support\StatementFile::purchaseName($purchaseOrder->supplier_name, $purchaseOrder->created_at, $this->poSeq($purchaseOrder)))
            );
        }

        $hq = User::where('role', 'hq')->orWhere('is_admin', true)->get();
        app(\App\Services\Notification\NotificationService::class)->notifyUsers(
            $hq, 'purchase_order', '🧾 구매 거래명세서 발행',
            "«{$purchaseOrder->supplier_name}»이(가) 구매발주 «{$purchaseOrder->po_no}»의 거래명세서를 발행했습니다.",
            ['purchase_order_id' => $purchaseOrder->id]
        );

        return back()->with('success', '거래명세서를 발행했습니다. 본사에서 확인할 수 있습니다.');
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
