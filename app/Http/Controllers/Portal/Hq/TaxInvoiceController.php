<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Statement;
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
        $searched = $request->boolean('searched');

        $store = $storeId ? Store::find($storeId) : null;
        $orders = collect();
        if ($searched) {
            // 매장 미지정(전체)이면 모든 매장의 미발행 발주를 조회
            $orders = Order::query()
                ->where('order_type', 'normal')
                ->where('status', '!=', 'canceled')
                ->whereNull('tax_invoice_id')
                ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
                ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
                ->withCount('items')
                ->with(['items', 'store'])
                ->latest('created_at')
                ->get();
        }

        return view('portal.hq.tax_invoices.create', [
            'stores' => Store::active()->orderBy('name')->get(['id', 'name', 'biz_no', 'email']),
            'store' => $store,
            'orders' => $orders,
            'searched' => $searched,
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
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer'],
        ], [
            'order_ids.required' => '발행할 발주를 한 건 이상 선택해 주세요.',
        ]);

        $orders = Order::where('order_type', 'normal')
            ->whereNull('tax_invoice_id')
            ->whereIn('id', $data['order_ids'])
            ->with(['items', 'store'])
            ->get();

        if ($orders->isEmpty()) {
            return back()->withErrors(['order_ids' => '발행 가능한 발주가 없습니다. (이미 발행되었거나 취소됨)'])->withInput();
        }
        // 세금계산서는 매장(공급받는자) 1곳 기준 → 선택 발주가 모두 같은 매장이어야 함
        if ($orders->pluck('store_id')->unique()->count() > 1) {
            return back()->withErrors(['order_ids' => '서로 다른 매장의 발주는 한 장으로 발행할 수 없습니다. 같은 매장 발주만 선택하세요.'])->withInput();
        }
        $store = $orders->first()->store;

        try {
            $invoices = $service->hqToStoreOrders($store, $orders);
        } catch (\Throwable $e) {
            return back()->withErrors(['order_ids' => '세금계산서 발행 실패: '.$e->getMessage()])->withInput();
        }

        return redirect()->route('portal.hq.tax_invoices.index')
            ->with('success', $this->resultMessage($invoices, $orders->count()));
    }

    /** 발주 상세에서 단건 발행 (본사 → 매장) */
    public function issueForOrder(Request $request, Order $order, TaxInvoiceIssueService $service)
    {
        if ($order->tax_invoice_id) {
            return back()->withErrors(['tax' => '이미 이 발주에 대한 세금계산서가 발행되었습니다.']);
        }

        try {
            $invoices = $service->hqToStore($order);
        } catch (\Throwable $e) {
            return back()->withErrors(['tax' => '세금계산서 발행 실패: '.$e->getMessage()]);
        }

        return back()->with('success', $this->resultMessage($invoices, 1));
    }

    /** 거래명세서 1건 → 세금계산서 발행 (본사 → 매장) */
    public function issueForStatement(Request $request, Statement $statement, TaxInvoiceIssueService $service)
    {
        if ($statement->tax_invoice_id) {
            return back()->withErrors(['tax' => '이미 이 거래명세서로 세금계산서가 발행되었습니다.']);
        }

        try {
            $invoices = $service->hqToStoreFromStatement($statement);
        } catch (\Throwable $e) {
            return back()->withErrors(['tax' => '세금계산서 발행 실패: '.$e->getMessage()]);
        }

        return back()->with('success', $this->resultMessage($invoices, 1));
    }

    /** 발행취소 (본사 → 매장) */
    public function cancel(Request $request, TaxInvoice $invoice, TaxInvoiceIssueService $service)
    {
        abort_unless($invoice->direction === 'hq_to_store', 403);

        try {
            $service->cancel($invoice, $request->input('memo'));
        } catch (\Throwable $e) {
            return back()->withErrors(['cancel' => $e->getMessage()]);
        }

        return back()->with('success', "세금계산서를 발행취소했습니다. (계산서번호 {$invoice->invoice_no})");
    }

    /** 발행 결과 메시지 (과세·면세 분리 시 2건 안내) */
    private function resultMessage(\Illuminate\Support\Collection $invoices, int $orderCount): string
    {
        $nos = $invoices->pluck('invoice_no')->implode(', ');
        $total = $invoices->sum('total_amount');
        $kind = $invoices->count() > 1 ? '세금계산서·계산서 2건' : ($invoices->first()->note ?? '세금계산서');

        return "{$kind}을(를) 발행했습니다. (번호 {$nos}, 발주 {$orderCount}건, 합계 ".number_format($total).'원)';
    }
}
