<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\TaxInvoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /** 본사가 공급처로부터 받은(수취) 세금계산서 목록 */
    public function index(Request $request)
    {
        $invoices = TaxInvoice::with('supplier')
            ->latest('issue_date')->latest()
            ->paginate(20);

        $totals = [
            'count' => TaxInvoice::where('status', 'issued')->count(),
            'amount' => (int) TaxInvoice::where('status', 'issued')->sum('total_amount'),
        ];

        return view('portal.hq.invoices.index', compact('invoices', 'totals'));
    }

    public function show(TaxInvoice $invoice)
    {
        $invoice->load(['supplier', 'items.order.store']);

        return view('portal.hq.invoices.show', compact('invoice'));
    }
}
