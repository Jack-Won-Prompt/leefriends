<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Mail\StatementMail;
use App\Mail\SupplierStatementMail;
use App\Models\Statement;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\SupplierStatement;
use App\Models\SupplyProduct;
use App\Services\TaxInvoice\TaxInvoiceIssueService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * 본사/공급처 거래명세서 — 작성/전송/이력/발행.
 *  - 본사(hq)   : 매장·품목 선택 → 작성·이메일 전송 (매장 판매가 기준), 재전송
 *  - 공급처(sup): 자사 품목 선택 → 작성 저장 → 본사 전송 / 세금계산서 발행 (공급가 기준)
 */
class StatementController extends Controller
{
    use ResolvesSeller;

    /** GET /seller/statements — 이력 */
    public function index(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        if ($type === 'hq') {
            $page = Statement::with('taxInvoice')->latest('sent_at')->paginate(20);
            $list = $page->getCollection()->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->store_name,
                'sub' => $s->email,
                'item_count' => (int) $s->item_count,
                'total' => (int) $s->total,
                'date' => $s->issueDate()->toDateString(),
                'resend_count' => (int) $s->resend_count,
                'invoiced' => (bool) $s->tax_invoice_id,
            ]);
        } else {
            $page = SupplierStatement::where('supplier_id', $sid)->with('taxInvoice')->latest()->paginate(20);
            $list = $page->getCollection()->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->statement_no,
                'sub' => $s->supplier_name,
                'item_count' => (int) $s->item_count,
                'total' => (int) $s->total,
                'date' => optional($s->created_at)->toDateString(),
                'emailed' => (bool) $s->emailed_at,
                'invoiced' => (bool) $s->tax_invoice_id,
            ]);
        }

        return response()->json([
            'data' => ['role' => $type, 'statements' => $list->values()],
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /** GET /seller/statements/catalog — 작성용 품목 카탈로그 (+본사: 매장 목록) */
    public function catalog(Request $request): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        if ($type === 'hq') {
            $catalog = SupplyProduct::active()->approved()->with('units')->catalogOrder()->get()
                ->map(fn ($p) => $this->catalogRow($p, 'store'));

            return response()->json(['data' => [
                'role' => 'hq',
                'stores' => Store::active()->orderBy('name')->get(['id', 'name', 'email'])
                    ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'email' => $s->email, 'has_email' => (bool) $s->email])->values(),
                'catalog' => $catalog->values(),
            ]]);
        }

        $catalog = SupplyProduct::where('supplier_id', $sid)->where('is_active', true)
            ->with('units')->catalogOrder()->get()
            ->map(fn ($p) => $this->catalogRow($p, 'supply'));

        return response()->json(['data' => ['role' => 'supplier', 'stores' => [], 'catalog' => $catalog->values()]]);
    }

    private function catalogRow($p, string $mode): array
    {
        $u = $p->units->firstWhere('is_default', true) ?? $p->units->first();

        return [
            'id' => $p->id,
            'code' => $p->code,
            'name' => $p->name,
            'category' => $p->category,
            'unit' => $u->name ?? $p->unit,
            'price' => (int) ($mode === 'store' ? ($u->store_price ?? $p->store_price) : ($u->supply_price ?? $p->supply_price)),
        ];
    }

    /**
     * POST /seller/statements — 작성/전송
     *  - 본사   : {store_id, items[{product_id, qty}]} → 매장 이메일로 PDF 전송 + 이력 저장
     *  - 공급처 : {items[{product_id, qty}], send?} → 작성 저장 (+본사 전송)
     */
    public function store(Request $request, TaxInvoiceIssueService $service): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        return $type === 'hq'
            ? $this->hqSend($request)
            : $this->supplierCreate($request, $sid);
    }

    private function hqSend(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'statement_date' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:supply_products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99999'],
        ], ['items.required' => '품목을 1개 이상 선택해 주세요.']);

        $store = Store::findOrFail($data['store_id']);
        if (! $store->email) {
            return response()->json(['message' => "«{$store->name}» 매장에 이메일이 없습니다. 매장 관리에서 이메일을 먼저 등록하세요."], 422);
        }

        [$lines, $total] = $this->buildHqLines($data['items']);
        if (empty($lines)) {
            return response()->json(['message' => '유효한 품목이 없습니다.'], 422);
        }

        $date = ! empty($data['statement_date'])
            ? \Illuminate\Support\Carbon::parse($data['statement_date'])->startOfDay()
            : now();

        $this->mailHqStatement($store, $lines, $total, null, $date);

        Statement::create([
            'store_id' => $store->id,
            'store_name' => $store->name,
            'email' => $store->email,
            'statement_date' => $date->toDateString(),
            'item_count' => count($lines),
            'total' => $total,
            'items' => $lines,
            'sent_by' => Auth::id(),
            'sent_at' => now(),
        ]);

        return response()->json([
            'message' => "«{$store->name}»({$store->email})로 거래명세서를 전송했습니다. (발행일자 {$date->toDateString()})",
        ], 201);
    }

    /** 본사 라인 산출 (단가는 서버 DB 기준 재계산) */
    private function buildHqLines(array $items): array
    {
        $products = SupplyProduct::with('units')
            ->whereIn('id', collect($items)->pluck('product_id'))->get()->keyBy('id');

        $lines = [];
        $total = 0;
        foreach ($items as $it) {
            $p = $products[$it['product_id']] ?? null;
            if (! $p) {
                continue;
            }
            $u = $p->units->firstWhere('is_default', true) ?? $p->units->first();
            $price = (int) ($u->store_price ?? $p->store_price);
            $qty = (int) $it['qty'];
            $amount = $price * $qty;
            $lines[] = [
                'code' => $p->code,
                'name' => $p->name,
                'unit' => $u->name ?? $p->unit,
                'qty' => $qty,
                'price' => $price,
                'amount' => $amount,
            ];
            $total += $amount;
        }

        return [$lines, $total];
    }

    private function mailHqStatement(Store $store, array $lines, int $total, ?string $email = null, ?\Illuminate\Support\Carbon $date = null): void
    {
        $date ??= now();
        $pdf = Pdf::loadView('portal.hq.statements.pdf', [
            'store' => $store, 'lines' => $lines, 'total' => $total, 'date' => $date,
        ])->setPaper('a4');
        $fileName = '거래명세서_'.$store->name.'_'.$date->format('Ymd').'.pdf';
        Mail::to($email ?: $store->email)->send(new StatementMail($store, $lines, $total, $pdf->output(), $fileName));
    }

    private function supplierCreate(Request $request, ?int $sid): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99999'],
            'send' => ['nullable', 'boolean'],
        ], ['items.required' => '품목을 1개 이상 선택해 주세요.']);

        $supplier = Supplier::findOrFail($sid);

        $products = SupplyProduct::with('units')
            ->where('supplier_id', $sid)
            ->whereIn('id', collect($data['items'])->pluck('product_id'))
            ->get()->keyBy('id');

        $lines = [];
        $supplyTotal = 0;
        $vatTotal = 0;
        foreach ($data['items'] as $it) {
            $p = $products[$it['product_id']] ?? null;
            if (! $p) {
                continue;
            }
            $u = $p->units->firstWhere('is_default', true) ?? $p->units->first();
            $price = (int) ($u->supply_price ?? $p->supply_price);
            $qty = (int) $it['qty'];
            $amount = $price * $qty;
            $taxType = $p->tax_type ?? 'inc';
            [$supply, $tax] = SupplyProduct::taxBreakdown($taxType, $amount);
            $lines[] = [
                'item_id' => null, 'product_id' => $p->id, 'code' => $p->code,
                'order_no' => '', 'store_name' => '',
                'name' => $p->name, 'unit' => $u->name ?? $p->unit,
                'qty' => $qty, 'unit_price' => $price, 'amount' => $amount,
                'tax_type' => $taxType, 'supply' => $supply, 'tax' => $tax,
            ];
            $supplyTotal += $supply;
            $vatTotal += $tax;
        }

        if (empty($lines)) {
            return response()->json(['message' => '유효한 품목이 없습니다. (내 공급품목만 담을 수 있습니다)'], 422);
        }

        $statement = SupplierStatement::create([
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'statement_no' => 'SS'.now()->format('YmdHisv'),
            'item_count' => count($lines),
            'supply_total' => $supplyTotal,
            'vat' => $vatTotal,
            'total' => $supplyTotal + $vatTotal,
            'items' => $lines,
            'created_by' => Auth::id(),
        ]);

        $message = '거래명세서를 작성했습니다.';
        if ($request->boolean('send')) {
            $to = $this->mailSupplierToHq($statement);
            $message = $to
                ? "거래명세서를 작성하고 본사({$to})로 전송했습니다."
                : '거래명세서를 작성했습니다. (본사 수신 이메일 미설정으로 전송은 보류)';
        }

        return response()->json(['message' => $message, 'data' => ['id' => $statement->id]], 201);
    }

    /** GET /seller/statements/{id} — 상세 */
    public function show(Request $request, int $id): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        if ($type === 'hq') {
            $s = Statement::with('taxInvoice')->findOrFail($id);

            return response()->json(['data' => [
                'role' => 'hq',
                'id' => $s->id,
                'title' => $s->store_name,
                'email' => $s->email,
                'date' => $s->issueDate()->toDateString(),
                'total' => (int) $s->total,
                'invoiced' => (bool) $s->tax_invoice_id,
                'can_resend' => true,
                'can_issue' => false,
                'items' => collect($s->items ?? [])->map(fn ($l) => [
                    'name' => $l['name'] ?? '-',
                    'unit' => $l['unit'] ?? '',
                    'qty' => (int) ($l['qty'] ?? 0),
                    'unit_price' => (int) ($l['price'] ?? $l['unit_price'] ?? 0),
                    'amount' => (int) ($l['amount'] ?? 0),
                ])->values(),
            ]]);
        }

        $s = SupplierStatement::with('taxInvoice')->where('supplier_id', $sid)->findOrFail($id);

        return response()->json(['data' => [
            'role' => 'supplier',
            'id' => $s->id,
            'title' => $s->statement_no,
            'email' => null,
            'date' => optional($s->created_at)->toDateString(),
            'total' => (int) $s->total,
            'supply_total' => (int) $s->supply_total,
            'vat' => (int) $s->vat,
            'invoiced' => (bool) $s->tax_invoice_id,
            'emailed' => (bool) $s->emailed_at,
            'can_resend' => true,
            'can_issue' => ! $s->tax_invoice_id,
            'items' => collect($s->items ?? [])->map(fn ($l) => [
                'name' => $l['name'] ?? '-',
                'unit' => $l['unit'] ?? '',
                'qty' => (int) ($l['qty'] ?? 0),
                'unit_price' => (int) ($l['unit_price'] ?? 0),
                'amount' => (int) ($l['amount'] ?? 0),
            ])->values(),
        ]]);
    }

    /** POST /seller/statements/{id}/send — 본사: 재전송 / 공급처: 본사로 전송 */
    public function send(Request $request, int $id): JsonResponse
    {
        [$type, $sid] = $this->seller($request);

        if ($type === 'hq') {
            $s = Statement::findOrFail($id);
            $store = $s->storeForRender();
            $email = $store->email ?: $s->email;
            if (! $email) {
                return response()->json(['message' => '수신 이메일이 없어 재전송할 수 없습니다.'], 422);
            }
            $this->mailHqStatement($store, $s->items, $s->total, $email);
            $s->increment('resend_count');
            $s->update(['sent_at' => now()]);

            return response()->json(['message' => "«{$s->store_name}»({$email})로 거래명세서를 재전송했습니다."]);
        }

        $s = SupplierStatement::where('supplier_id', $sid)->findOrFail($id);
        $to = $this->mailSupplierToHq($s);
        if (! $to) {
            return response()->json(['message' => '본사 수신 이메일이 설정되어 있지 않습니다.'], 422);
        }

        return response()->json(['message' => "거래명세서를 본사({$to})로 전송했습니다."]);
    }

    /** POST /seller/statements/{id}/issue — (공급처) 거래명세서 → 세금계산서 발행 */
    public function issue(Request $request, int $id, TaxInvoiceIssueService $service): JsonResponse
    {
        [$type, $sid] = $this->seller($request);
        abort_unless($type === 'supplier', 403, '공급처 계정만 발행할 수 있습니다.');

        $statement = SupplierStatement::where('supplier_id', $sid)->findOrFail($id);
        if ($statement->tax_invoice_id) {
            return response()->json(['message' => '이미 이 거래명세서로 세금계산서가 발행되었습니다.'], 422);
        }

        try {
            $invoices = $service->supplierToHqFromStatement($statement);
            $statement->update(['tax_invoice_id' => $invoices->first()->id]);
        } catch (\Throwable $e) {
            return response()->json(['message' => '세금계산서 발행 실패: '.$e->getMessage()], 422);
        }

        $msg = $invoices->count() > 1
            ? '세금계산서·계산서 2건이 발행되었습니다. (과세/면세 분리, 본사 청구)'
            : '세금계산서가 발행되었습니다. (본사 청구)';

        return response()->json([
            'message' => $msg,
            'data' => ['invoice_ids' => $invoices->pluck('id')->values()],
        ], 201);
    }

    /** 공급처 거래명세서 PDF를 본사로 전송. 수신주소 반환(미설정 시 null). */
    private function mailSupplierToHq(SupplierStatement $statement): ?string
    {
        $to = config('popbill.hq.email') ?: config('mail.from.address');
        if (! $to) {
            return null;
        }

        $statement->loadMissing('supplier');
        $pdf = Pdf::loadView('portal.supplier.statements.pdf', ['statement' => $statement])->setPaper('a4');
        $fileName = '거래명세서_'.$statement->statement_no.'.pdf';

        Mail::to($to)->send(new SupplierStatementMail($statement, $pdf->output(), $fileName));
        $statement->update(['emailed_at' => now(), 'email_count' => $statement->email_count + 1]);

        return $to;
    }
}
