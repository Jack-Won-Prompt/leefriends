<?php

namespace App\Http\Controllers\Portal\Supplier;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Supplier;
use App\Models\TaxInvoice;
use App\Services\TaxInvoice\TaxInvoiceIssueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index()
    {
        $sid = Auth::user()->supplier_id;

        $invoices = TaxInvoice::where('supplier_id', $sid)->latest('issue_date')->latest()->paginate(15);

        // 배송완료 + 미청구 금액(공급가 기준)
        $uninvoiced = OrderItem::forSupplier($sid)
            ->where('fulfillment_status', 'delivered')
            ->whereNull('tax_invoice_id');

        $pending = [
            'count' => (clone $uninvoiced)->count(),
            'amount' => (int) (clone $uninvoiced)->sum('supply_line_amount'),
        ];

        return view('portal.supplier.invoices.index', compact('invoices', 'pending'));
    }

    public function create()
    {
        $sid = Auth::user()->supplier_id;

        $items = OrderItem::forSupplier($sid)
            ->where('fulfillment_status', 'delivered')
            ->whereNull('tax_invoice_id')
            ->with('order.store')
            ->orderBy('order_id')
            ->get();

        return view('portal.supplier.invoices.create', compact('items'));
    }

    public function store(Request $request, TaxInvoiceIssueService $service)
    {
        $sid = Auth::user()->supplier_id;

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['integer'],
            'issue_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'items.required' => '청구할 배송완료 품목을 선택해 주세요.',
        ]);

        // 본사가 정한 공급가 기준으로만 집계 (소유/상태 재검증)
        $items = OrderItem::forSupplier($sid)
            ->where('fulfillment_status', 'delivered')
            ->whereNull('tax_invoice_id')
            ->whereIn('id', $data['items'])
            ->get();

        if ($items->isEmpty()) {
            return back()->withErrors(['items' => '청구 가능한 품목이 없습니다.'])->withInput();
        }

        $supplier = Supplier::findOrFail($sid);

        try {
            // 제품별 부가세구분 반영 + 팝빌 즉시발행 (공급처 → 본사).
            // 과세→세금계산서 / 면세→계산서 자동 분리, 품목 발행처리는 서비스에서 수행.
            $invoices = $service->supplierToHq($supplier, $items);
        } catch (\Throwable $e) {
            return back()->withErrors(['items' => '세금계산서 발행 실패: '.$e->getMessage()])->withInput();
        }

        $msg = $invoices->count() > 1
            ? '세금계산서·계산서 2건이 발행되었습니다. (과세/면세 분리, 본사 청구)'
            : '세금계산서가 발행되었습니다. (본사 청구)';

        return redirect()->route('portal.supplier.invoices.index')->with('success', $msg);
    }

    public function cancel(Request $request, TaxInvoice $invoice, TaxInvoiceIssueService $service)
    {
        abort_unless($invoice->supplier_id === Auth::user()->supplier_id, 403);

        try {
            $service->cancel($invoice, $request->input('memo'));
        } catch (\Throwable $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }

        return back()->with('success', "세금계산서를 발행취소했습니다. (계산서번호 {$invoice->invoice_no})");
    }

    private function generateInvoiceNo(): string
    {
        $date = now()->format('Ymd');
        $seq = TaxInvoice::whereDate('created_at', today())->count() + 1;

        return sprintf('TI-%s-%03d', $date, $seq);
    }
}
