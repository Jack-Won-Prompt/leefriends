<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TaxInvoice;
use App\Services\TaxInvoice\TaxInvoiceIssueService;
use Illuminate\Http\Request;

/**
 * 본사 → 매장 전자세금계산서 발행/이력.
 */
class TaxInvoiceController extends Controller
{
    /** 발행 이력 (본사→매장) */
    public function index()
    {
        return view('portal.hq.tax_invoices.index', [
            'invoices' => TaxInvoice::where('direction', 'hq_to_store')
                ->with(['store', 'order', 'issuer'])->latest()->paginate(20),
        ]);
    }

    /** 매장 발주 1건 → 본사가 매장에 세금계산서 발행 */
    public function issueForOrder(Request $request, Order $order, TaxInvoiceIssueService $service)
    {
        // 이미 발행된 주문이면 중복 방지
        if (TaxInvoice::where('direction', 'hq_to_store')->where('order_id', $order->id)->where('status', 'issued')->exists()) {
            return back()->withErrors(['tax' => '이미 이 발주에 대한 세금계산서가 발행되었습니다.']);
        }

        try {
            $invoice = $service->hqToStore($order);
        } catch (\Throwable $e) {
            return back()->withErrors(['tax' => '세금계산서 발행 실패: '.$e->getMessage()]);
        }

        return back()->with('success', "세금계산서를 발행했습니다. (계산서번호 {$invoice->invoice_no}, 합계 ".number_format($invoice->total_amount)."원)");
    }
}
