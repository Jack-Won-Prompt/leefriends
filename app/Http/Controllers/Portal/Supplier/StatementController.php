<?php

namespace App\Http\Controllers\Portal\Supplier;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Supplier;
use App\Models\SupplierStatement;
use App\Models\SupplyProduct;
use App\Services\TaxInvoice\TaxInvoiceIssueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * 공급처 거래명세서: 배송완료 품목 선택 → 작성·저장 → 이력에서 선택 발행(세금계산서).
 */
class StatementController extends Controller
{
    public function index()
    {
        $sid = Auth::user()->supplier_id;

        return view('portal.supplier.statements.index', [
            'statements' => SupplierStatement::where('supplier_id', $sid)
                ->with('taxInvoice')->latest()->paginate(20),
        ]);
    }

    public function create()
    {
        $sid = Auth::user()->supplier_id;

        return view('portal.supplier.statements.create', [
            'catalog' => $this->catalog($sid),
        ]);
    }

    public function store(Request $request)
    {
        $sid = Auth::user()->supplier_id;

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99999'],
        ], ['items.required' => '품목을 1개 이상 선택해 주세요.']);

        $supplier = Supplier::findOrFail($sid);

        // 내 공급품목만 (소유 검증)
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
                'item_id' => null,
                'product_id' => $p->id,
                'code' => $p->code,
                'order_no' => '',
                'store_name' => '',
                'name' => $p->name,
                'unit' => $u->name ?? $p->unit,
                'qty' => $qty,
                'unit_price' => $price,
                'amount' => $amount,
                'tax_type' => $taxType,
                'supply' => $supply,
                'tax' => $tax,
            ];
            $supplyTotal += $supply;
            $vatTotal += $tax;
        }

        if (empty($lines)) {
            return back()->withErrors(['items' => '유효한 품목이 없습니다. (내 공급품목만 담을 수 있습니다)'])->withInput();
        }

        $statement = SupplierStatement::create([
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'statement_no' => $this->statementNo(),
            'item_count' => count($lines),
            'supply_total' => $supplyTotal,
            'vat' => $vatTotal,
            'total' => $supplyTotal + $vatTotal,
            'items' => $lines,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('portal.supplier.statements.show', $statement)
            ->with('success', '거래명세서를 작성했습니다. 이력에서 세금계산서를 발행할 수 있습니다.');
    }

    /** 내 공급품목 카탈로그 (공급가 기준) */
    private function catalog(int $sid)
    {
        return SupplyProduct::where('supplier_id', $sid)
            ->where('is_active', true)
            ->with('units')->catalogOrder()->get()->map(function ($p) {
                $u = $p->units->firstWhere('is_default', true) ?? $p->units->first();

                return [
                    'id' => $p->id,
                    'code' => $p->code,
                    'name' => $p->name,
                    'category' => $p->category,
                    'unit' => $u->name ?? $p->unit,
                    'price' => (int) ($u->supply_price ?? $p->supply_price),
                ];
            })->values();
    }

    public function show(SupplierStatement $statement)
    {
        abort_unless($statement->supplier_id === Auth::user()->supplier_id, 403);
        $statement->load('taxInvoice');

        return view('portal.supplier.statements.show', compact('statement'));
    }

    /** 거래명세서 → 세금계산서 발행 (공급처 → 본사) */
    public function issue(SupplierStatement $statement, TaxInvoiceIssueService $service)
    {
        abort_unless($statement->supplier_id === Auth::user()->supplier_id, 403);

        if ($statement->tax_invoice_id) {
            return back()->withErrors(['tax' => '이미 이 거래명세서로 세금계산서가 발행되었습니다.']);
        }

        try {
            $invoices = $service->supplierToHqFromStatement($statement);
            $statement->update(['tax_invoice_id' => $invoices->first()->id]);
        } catch (\Throwable $e) {
            return back()->withErrors(['tax' => '세금계산서 발행 실패: '.$e->getMessage()]);
        }

        $msg = $invoices->count() > 1
            ? '세금계산서·계산서 2건이 발행되었습니다. (과세/면세 분리, 본사 청구)'
            : '세금계산서가 발행되었습니다. (본사 청구)';

        return redirect()->route('portal.supplier.statements.show', $statement)->with('success', $msg);
    }

    /** 미발행 거래명세서 삭제 (귀속 품목 해제) */
    public function destroy(SupplierStatement $statement)
    {
        abort_unless($statement->supplier_id === Auth::user()->supplier_id, 403);

        if ($statement->tax_invoice_id) {
            return back()->withErrors(['tax' => '이미 세금계산서가 발행된 거래명세서는 삭제할 수 없습니다.']);
        }

        DB::transaction(function () use ($statement) {
            $statement->orderItems()->update(['supplier_statement_id' => null]);
            $statement->delete();
        });

        return redirect()->route('portal.supplier.statements.index')->with('success', '거래명세서를 삭제했습니다.');
    }

    private function statementNo(): string
    {
        return 'SS'.now()->format('YmdHisv');
    }
}
