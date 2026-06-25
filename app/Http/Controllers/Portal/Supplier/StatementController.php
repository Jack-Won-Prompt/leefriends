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

        // 배송완료 + 미청구(tax_invoice 없음) + 다른 명세서에 미포함
        $items = OrderItem::forSupplier($sid)
            ->where('fulfillment_status', 'delivered')
            ->whereNull('tax_invoice_id')
            ->whereNull('supplier_statement_id')
            ->with('order.store')
            ->orderBy('order_id')
            ->get();

        return view('portal.supplier.statements.create', compact('items'));
    }

    public function store(Request $request)
    {
        $sid = Auth::user()->supplier_id;

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*' => ['integer'],
        ], ['items.required' => '거래명세서에 담을 배송완료 품목을 선택해 주세요.']);

        $items = OrderItem::forSupplier($sid)
            ->where('fulfillment_status', 'delivered')
            ->whereNull('tax_invoice_id')
            ->whereNull('supplier_statement_id')
            ->whereIn('id', $data['items'])
            ->with('order.store')
            ->get();

        if ($items->isEmpty()) {
            return back()->withErrors(['items' => '담을 수 있는 품목이 없습니다.'])->withInput();
        }

        $supplier = Supplier::findOrFail($sid);
        $taxTypes = SupplyProduct::whereIn('id', $items->pluck('supply_product_id'))->pluck('tax_type', 'id');

        $lines = [];
        $supplyTotal = 0;
        $vatTotal = 0;
        foreach ($items as $it) {
            $amount = (int) $it->supply_line_amount;
            $taxType = $taxTypes[$it->supply_product_id] ?? 'inc';
            [$supply, $tax] = SupplyProduct::taxBreakdown($taxType, $amount);
            $lines[] = [
                'item_id' => $it->id,
                'order_no' => $it->order->order_no ?? '',
                'store_name' => $it->order->store->name ?? '',
                'name' => $it->product_name,
                'unit' => $it->unit,
                'qty' => (int) $it->qty,
                'unit_price' => (int) $it->supply_unit_price,
                'amount' => $amount,
                'tax_type' => $taxType,
                'supply' => $supply,
                'tax' => $tax,
            ];
            $supplyTotal += $supply;
            $vatTotal += $tax;
        }

        $statement = DB::transaction(function () use ($supplier, $items, $lines, $supplyTotal, $vatTotal) {
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
            // 품목을 이 명세서에 귀속(중복 포함 방지)
            OrderItem::whereIn('id', $items->pluck('id'))->update(['supplier_statement_id' => $statement->id]);

            return $statement;
        });

        return redirect()->route('portal.supplier.statements.show', $statement)
            ->with('success', '거래명세서를 작성했습니다. 이력에서 세금계산서를 발행할 수 있습니다.');
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

        // 귀속 품목 중 아직 미청구만
        $items = $statement->orderItems()->whereNull('tax_invoice_id')->get();
        if ($items->isEmpty()) {
            return back()->withErrors(['tax' => '발행 가능한 품목이 없습니다.']);
        }

        try {
            $invoices = $service->supplierToHq($statement->supplier, $items);
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
