<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\TaxInvoice;
use App\Services\TaxInvoice\TaxInvoiceIssueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 본사/공급처 전자세금계산서 — 발행 대상 조회, 발행, 취소, 이력.
 *  - 본사(hq)   : 본사 → 매장 (미발행 발주 선택 발행)
 *  - 공급처(sup): 공급처 → 본사 (배송완료·미청구 품목 선택 발행)
 */
class TaxInvoiceController extends Controller
{
    use ResolvesSeller;

    /** GET /seller/tax-invoices — 발행 이력 */
    public function index(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        $invoices = TaxInvoice::query()
            ->when($type === 'hq',
                fn ($q) => $q->where('direction', 'hq_to_store'),
                fn ($q) => $q->where('direction', 'supplier_to_hq')->where('supplier_id', $sid))
            ->latest('issue_date')->latest()
            ->paginate(20);

        return response()->json([
            'data' => $invoices->getCollection()->map(fn ($i) => $this->summary($i))->values(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    /** GET /seller/tax-invoices/stores — (본사) 활성 매장 목록 */
    public function stores(Request $request): JsonResponse
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');

        return response()->json([
            'data' => Store::active()->orderBy('name')
                ->get(['id', 'name', 'biz_no'])
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'biz_no' => $s->biz_no,
                    'has_biz_no' => (bool) $s->biz_no,
                ])->values(),
        ]);
    }

    /**
     * GET /seller/tax-invoices/issuable — 발행 가능 대상
     *  - 본사   : ?store_id=&from=&to= → 미발행 발주 목록
     *  - 공급처 : 배송완료·미청구 품목 목록
     */
    public function issuable(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        return $type === 'hq'
            ? $this->hqIssuable($request)
            : $this->supplierIssuable($sid);
    }

    private function hqIssuable(Request $request): JsonResponse
    {
        $storeId = $request->integer('store_id') ?: null;
        if (! $storeId) {
            return response()->json(['data' => ['mode' => 'orders', 'store' => null, 'orders' => []]]);
        }

        $store = Store::find($storeId);
        abort_unless($store, 404, '매장을 찾을 수 없습니다.');

        $from = $request->date('from');
        $to = $request->date('to');

        $orders = Order::where('store_id', $storeId)
            ->where('order_type', 'normal')
            ->where('status', '!=', 'canceled')
            ->whereNull('tax_invoice_id')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->withCount('items')
            ->latest('created_at')
            ->get();

        return response()->json(['data' => [
            'mode' => 'orders',
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'biz_no' => $store->biz_no,
                'has_biz_no' => (bool) $store->biz_no,
            ],
            'orders' => $orders->map(fn ($o) => [
                'id' => $o->id,
                'order_no' => $o->order_no,
                'created_at' => optional($o->created_at)->toDateString(),
                'item_count' => (int) $o->items_count,
                'amount' => (int) $o->store_amount,
            ])->values(),
        ]]);
    }

    private function supplierIssuable(?int $sid): JsonResponse
    {
        $items = OrderItem::forSupplier($sid)
            ->where('fulfillment_status', 'delivered')
            ->whereNull('tax_invoice_id')
            ->with('order.store')
            ->orderBy('order_id')
            ->get();

        return response()->json(['data' => [
            'mode' => 'items',
            'store' => null,
            'items' => $items->map(fn ($it) => [
                'id' => $it->id,
                'product_name' => $it->product_name,
                'unit' => $it->unit,
                'qty' => (int) $it->qty,
                'amount' => (int) $it->supply_line_amount,
                'order_no' => optional($it->order)->order_no,
                'store_name' => optional(optional($it->order)->store)->name,
            ])->values(),
        ]]);
    }

    /**
     * POST /seller/tax-invoices/issue — 발행
     *  - 본사   : {store_id, order_ids[]}
     *  - 공급처 : {item_ids[]}
     */
    public function issue(Request $request, TaxInvoiceIssueService $service): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        return $type === 'hq'
            ? $this->hqIssue($request, $service)
            : $this->supplierIssue($request, $sid, $service);
    }

    private function hqIssue(Request $request, TaxInvoiceIssueService $service): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer'],
        ], ['order_ids.required' => '발행할 발주를 한 건 이상 선택해 주세요.']);

        $store = Store::findOrFail($data['store_id']);
        $orders = Order::where('store_id', $store->id)
            ->whereNull('tax_invoice_id')
            ->whereIn('id', $data['order_ids'])
            ->with(['items', 'store'])
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => '발행 가능한 발주가 없습니다. (이미 발행되었거나 취소됨)'], 422);
        }

        try {
            $invoices = $service->hqToStoreOrders($store, $orders);
        } catch (\Throwable $e) {
            return response()->json(['message' => '세금계산서 발행 실패: '.$e->getMessage()], 422);
        }

        return $this->issuedResponse($invoices, $orders->count());
    }

    private function supplierIssue(Request $request, ?int $sid, TaxInvoiceIssueService $service): JsonResponse
    {
        $data = $request->validate([
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer'],
        ], ['item_ids.required' => '청구할 배송완료 품목을 선택해 주세요.']);

        $items = OrderItem::forSupplier($sid)
            ->where('fulfillment_status', 'delivered')
            ->whereNull('tax_invoice_id')
            ->whereIn('id', $data['item_ids'])
            ->get();

        if ($items->isEmpty()) {
            return response()->json(['message' => '청구 가능한 품목이 없습니다.'], 422);
        }

        $supplier = Supplier::findOrFail($sid);

        try {
            $invoices = $service->supplierToHq($supplier, $items);
        } catch (\Throwable $e) {
            return response()->json(['message' => '세금계산서 발행 실패: '.$e->getMessage()], 422);
        }

        return $this->issuedResponse($invoices, $items->count());
    }

    /** GET /seller/tax-invoices/{invoice} — 상세 */
    public function show(Request $request, TaxInvoice $invoice): JsonResponse
    {
        $this->authorizeInvoice($request, $invoice);

        return response()->json(['data' => $this->detail($invoice)]);
    }

    /** POST /seller/tax-invoices/{invoice}/cancel — 발행취소 */
    public function cancel(Request $request, TaxInvoice $invoice, TaxInvoiceIssueService $service): JsonResponse
    {
        $this->authorizeInvoice($request, $invoice);

        try {
            $service->cancel($invoice, $request->input('memo'));
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "세금계산서를 발행취소했습니다. (계산서번호 {$invoice->invoice_no})",
            'data' => $this->summary($invoice->fresh()),
        ]);
    }

    /** 소유/권한 검증 */
    private function authorizeInvoice(Request $request, TaxInvoice $invoice): void
    {
        [$type, $sid] = $this->seller($request);

        if ($type === 'hq') {
            abort_unless($invoice->direction === 'hq_to_store', 403);
        } else {
            abort_unless($invoice->direction === 'supplier_to_hq' && (int) $invoice->supplier_id === (int) $sid, 403);
        }
    }

    private function issuedResponse(\Illuminate\Support\Collection $invoices, int $count): JsonResponse
    {
        $nos = $invoices->pluck('invoice_no')->implode(', ');
        $total = (int) $invoices->sum('total_amount');
        $msg = $invoices->count() > 1
            ? "세금계산서·계산서 2건을 발행했습니다. (과세/면세 분리, 번호 {$nos})"
            : "세금계산서를 발행했습니다. (번호 {$nos})";

        return response()->json([
            'message' => $msg,
            'data' => [
                'invoice_ids' => $invoices->pluck('id')->values(),
                'count' => $invoices->count(),
                'source_count' => $count,
                'total' => $total,
                'invoices' => $invoices->map(fn ($i) => $this->summary($i))->values(),
            ],
        ], 201);
    }

    /** 목록/요약 직렬화 */
    private function summary(TaxInvoice $i): array
    {
        return [
            'id' => $i->id,
            'invoice_no' => $i->invoice_no,
            'direction' => $i->direction,
            'direction_label' => $i->direction_label,
            'counterparty_name' => $i->invoicee_corp_name,
            'invoicer_name' => $i->invoicer_corp_name,
            'supply_amount' => (int) $i->supply_amount,
            'vat' => (int) $i->vat,
            'total_amount' => (int) $i->total_amount,
            'status' => $i->status,
            'status_label' => $i->status_label,
            'nts_confirm_num' => $i->nts_confirm_num,
            'issue_date' => optional($i->issue_date)->toDateString(),
            'note' => $i->note,
        ];
    }

    /** 상세 직렬화 (당사자 + 품목) */
    private function detail(TaxInvoice $i): array
    {
        return array_merge($this->summary($i), [
            'invoicer_corp_num' => $i->invoicer_corp_num,
            'invoicer_corp_name' => $i->invoicer_corp_name,
            'invoicee_corp_num' => $i->invoicee_corp_num,
            'invoicee_corp_name' => $i->invoicee_corp_name,
            'invoicee_email' => $i->invoicee_email,
            'can_cancel' => $i->status === 'issued',
            'line_items' => collect($i->line_items ?? [])->map(fn ($l) => [
                'name' => $l['name'] ?? '-',
                'spec' => $l['spec'] ?? '',
                'qty' => (int) ($l['qty'] ?? 0),
                'unit_price' => (int) ($l['unit_price'] ?? 0),
                'amount' => (int) (($l['supply'] ?? 0) + ($l['tax'] ?? 0)),
            ])->values(),
        ]);
    }
}
