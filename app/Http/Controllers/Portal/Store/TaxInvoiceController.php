<?php

namespace App\Http\Controllers\Portal\Store;

use App\Http\Controllers\Controller;
use App\Models\TaxInvoice;
use Illuminate\Support\Facades\Auth;

/**
 * 매장: 본사가 매장 앞으로 발행한 세금계산서 확인.
 */
class TaxInvoiceController extends Controller
{
    public function index()
    {
        $storeId = Auth::user()->store_id;

        $invoices = TaxInvoice::where('direction', 'hq_to_store')
            ->where('store_id', $storeId)
            ->latest('issue_date')->latest()
            ->paginate(20);

        $base = TaxInvoice::where('direction', 'hq_to_store')->where('store_id', $storeId);
        $totals = [
            'count' => (clone $base)->count(),
            'amount' => (int) (clone $base)->where('status', '!=', 'canceled')->sum('total_amount'),
            'month' => (clone $base)->where('status', '!=', 'canceled')
                ->whereYear('issue_date', now()->year)->whereMonth('issue_date', now()->month)->count(),
        ];

        return view('portal.store.tax_invoices.index', compact('invoices', 'totals'));
    }

    public function show(TaxInvoice $invoice)
    {
        abort_unless(
            $invoice->direction === 'hq_to_store' && $invoice->store_id === Auth::user()->store_id,
            403
        );

        return view('portal.store.tax_invoices.show', compact('invoice'));
    }
}
