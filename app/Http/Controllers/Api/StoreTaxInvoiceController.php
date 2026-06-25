<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 매장: 본사가 매장 앞으로 발행한 세금계산서 조회 (읽기 전용).
 */
class StoreTaxInvoiceController extends Controller
{
    private function storeId(Request $request): int
    {
        $id = $request->user()->store_id;
        abort_unless($id, 403, '연결된 매장이 없는 계정입니다.');

        return $id;
    }

    /**
     * GET /api/v1/store/tax-invoices
     */
    public function index(Request $request): JsonResponse
    {
        $storeId = $this->storeId($request);

        $invoices = TaxInvoice::where('direction', 'hq_to_store')
            ->where('store_id', $storeId)
            ->latest('issue_date')->latest()
            ->paginate(20);

        return response()->json([
            'data' => $invoices->getCollection()->map(fn (TaxInvoice $t) => $this->summary($t))->values(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/store/tax-invoices/{invoice}
     */
    public function show(Request $request, TaxInvoice $invoice): JsonResponse
    {
        abort_unless(
            $invoice->direction === 'hq_to_store' && $invoice->store_id === $this->storeId($request),
            403,
        );

        return response()->json(['data' => $this->detail($invoice)]);
    }

    private function summary(TaxInvoice $t): array
    {
        return [
            'id' => $t->id,
            'invoice_no' => $t->invoice_no,
            'invoicer_name' => $t->invoicer_corp_name, // 공급자(본사)
            'supply_amount' => (int) $t->supply_amount,
            'vat' => (int) $t->vat,
            'total_amount' => (int) $t->total_amount,
            'status' => $t->status,
            'status_label' => TaxInvoice::STATUSES[$t->status] ?? $t->status,
            'nts_confirm_num' => $t->nts_confirm_num,
            'issue_date' => $t->issue_date?->format('Y-m-d'),
        ];
    }

    private function detail(TaxInvoice $t): array
    {
        return array_merge($this->summary($t), [
            'invoicer_corp_num' => $t->invoicer_corp_num,
            'invoicer_corp_name' => $t->invoicer_corp_name,
            'invoicee_corp_num' => $t->invoicee_corp_num,
            'invoicee_corp_name' => $t->invoicee_corp_name,
            'invoicee_email' => $t->invoicee_email,
            'note' => $t->note,
            'line_items' => collect($t->line_items ?? [])->map(fn ($it) => [
                'name' => $it['name'] ?? ($it['itemName'] ?? ''),
                'qty' => (int) ($it['qty'] ?? ($it['quantity'] ?? 0)),
                'unit_price' => (int) ($it['unit_price'] ?? ($it['unitCost'] ?? 0)),
                'amount' => (int) ($it['amount'] ?? ($it['supplyCost'] ?? 0)),
            ])->values(),
        ]);
    }
}
