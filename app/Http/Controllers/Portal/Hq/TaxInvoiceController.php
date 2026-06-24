<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Store;
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

    /** 작성 화면: 매장 + 기간 선택 → 미발행 발주 목록 */
    public function create(Request $request)
    {
        $storeId = $request->integer('store_id') ?: null;
        $from = $request->date('from');
        $to = $request->date('to');

        $orders = collect();
        $store = null;
        if ($storeId) {
            $store = Store::find($storeId);
            $orders = Order::where('store_id', $storeId)
                ->where('order_type', 'normal')
                ->where('status', '!=', 'canceled')
                ->whereNull('tax_invoice_id')
                ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                ->withCount('items')
                ->with('items')
                ->latest('created_at')
                ->get();
        }

        return view('portal.hq.tax_invoices.create', [
            'stores' => Store::active()->orderBy('name')->get(['id', 'name', 'biz_no', 'email']),
            'store' => $store,
            'orders' => $orders,
            'filters' => [
                'store_id' => $storeId,
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ],
        ]);
    }

    /** 선택한 발주들을 한 매장 기준 1장으로 발행 */
    public function store(Request $request, TaxInvoiceIssueService $service)
    {
        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer'],
        ], [
            'order_ids.required' => '발행할 발주를 한 건 이상 선택해 주세요.',
        ]);

        $store = Store::findOrFail($data['store_id']);
        $orders = Order::where('store_id', $store->id)
            ->whereNull('tax_invoice_id')
            ->whereIn('id', $data['order_ids'])
            ->with(['items', 'store'])
            ->get();

        if ($orders->isEmpty()) {
            return back()->withErrors(['order_ids' => '발행 가능한 발주가 없습니다. (이미 발행되었거나 취소됨)'])->withInput();
        }

        try {
            $invoice = $service->hqToStoreOrders($store, $orders);
        } catch (\Throwable $e) {
            return back()->withErrors(['order_ids' => '세금계산서 발행 실패: '.$e->getMessage()])->withInput();
        }

        return redirect()->route('portal.hq.tax_invoices.index')
            ->with('success', "세금계산서를 발행했습니다. (계산서번호 {$invoice->invoice_no}, 발주 {$orders->count()}건, 합계 ".number_format($invoice->total_amount).'원)');
    }

    /** 발주 상세에서 단건 발행 (본사 → 매장) */
    public function issueForOrder(Request $request, Order $order, TaxInvoiceIssueService $service)
    {
        if ($order->tax_invoice_id) {
            return back()->withErrors(['tax' => '이미 이 발주에 대한 세금계산서가 발행되었습니다.']);
        }

        try {
            $invoice = $service->hqToStore($order);
        } catch (\Throwable $e) {
            return back()->withErrors(['tax' => '세금계산서 발행 실패: '.$e->getMessage()]);
        }

        return back()->with('success', "세금계산서를 발행했습니다. (계산서번호 {$invoice->invoice_no}, 합계 ".number_format($invoice->total_amount).'원)');
    }
}
