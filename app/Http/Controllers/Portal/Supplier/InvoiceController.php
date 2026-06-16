<?php

namespace App\Http\Controllers\Portal\Supplier;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\TaxInvoice;
use App\Services\TaxInvoice\TaxInvoiceIssuer;
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

    public function store(Request $request, TaxInvoiceIssuer $issuer)
    {
        $sid = Auth::user()->supplier_id;

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['integer'],
            'issue_date' => ['required', 'date'],
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

        $invoice = DB::transaction(function () use ($sid, $items, $data, $issuer) {
            $supply = (int) $items->sum('supply_line_amount'); // 공급가액 (본사가 정한 공급가 기준)
            $vat = (int) round($supply * 0.1);                 // 부가세 10%

            $invoice = TaxInvoice::create([
                'invoice_no' => $this->generateInvoiceNo(),
                'supplier_id' => $sid,
                'supply_amount' => $supply,
                'vat' => $vat,
                'total_amount' => $supply + $vat,
                'status' => 'pending',
                'issue_date' => $data['issue_date'],
                'note' => $data['note'] ?? null,
            ]);

            // 발행 처리 (internal → 추후 popbill 드라이버로 교체)
            $issuer->issue($invoice);

            OrderItem::whereIn('id', $items->pluck('id'))->update(['tax_invoice_id' => $invoice->id]);

            return $invoice;
        });

        return redirect()->route('portal.supplier.invoices.show', $invoice)
            ->with('success', '세금계산서가 발행되었습니다. (본사 청구)');
    }

    public function show(TaxInvoice $invoice)
    {
        abort_unless($invoice->supplier_id === Auth::user()->supplier_id, 403);
        $invoice->load(['supplier', 'items.order.store']);

        return view('portal.supplier.invoices.show', compact('invoice'));
    }

    private function generateInvoiceNo(): string
    {
        $date = now()->format('Ymd');
        $seq = TaxInvoice::whereDate('created_at', today())->count() + 1;

        return sprintf('TI-%s-%03d', $date, $seq);
    }
}
