<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\SupplierStatement;
use App\Models\SupplyProduct;
use App\Services\Inventory\HqStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 물류관리 · 입고관리 — 공급처 거래명세서 입고 처리 + 수동 입고. 입고 시 본사 재고 증가.
 */
class LogisticsInboundController extends Controller
{
    public function __construct(private HqStockService $stock)
    {
    }

    public function index(Request $request)
    {
        $filter = $request->query('status', 'all'); // all | pending | done

        $q = SupplierStatement::with('supplier')->latest('id');
        if ($filter === 'pending') {
            $q->whereNull('received_at');
        } elseif ($filter === 'done') {
            $q->whereNotNull('received_at');
        }
        $statements = $q->paginate(20)->withQueryString();

        $products = SupplyProduct::where('is_active', true)->orderBy('name')->get(['id', 'name', 'unit', 'code']);

        return view('portal.hq.logistics.inbound', compact('statements', 'products', 'filter'));
    }

    /** 공급처 명세서 입고 처리 → 재고 증가 */
    public function receive(SupplierStatement $statement)
    {
        if ($statement->received_at) {
            return back()->with('error', '이미 입고 처리된 명세서입니다.');
        }

        foreach (($statement->items ?? []) as $it) {
            $pid = (int) ($it['product_id'] ?? 0);
            $qty = (int) ($it['qty'] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $this->stock->inbound($pid, $it['name'] ?? '', $qty, 'statement', 'SupplierStatement', $statement->id, Auth::id(),
                    '명세서 '.$statement->statement_no);
            }
        }

        $statement->update(['received_at' => now(), 'received_by' => Auth::id()]);

        return back()->with('success', "명세서 {$statement->statement_no} 입고 처리 완료 — 본사 재고에 반영했습니다.");
    }

    /** 수동 입고 */
    public function manual(Request $request)
    {
        $data = $request->validate([
            'supply_product_id' => ['required', 'exists:supply_products,id'],
            'qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $p = SupplyProduct::findOrFail($data['supply_product_id']);
        $this->stock->inbound($p->id, $p->name, (int) $data['qty'], 'manual', null, null, Auth::id(), $data['note'] ?? '수동 입고');

        return back()->with('success', "{$p->name} {$data['qty']}{$p->unit} 입고했습니다.");
    }
}
