<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\SupplierStatement;
use App\Models\SupplyProduct;
use App\Services\Inventory\HqStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 본사 물류 입고 — 공급처 거래명세서 입고 처리 + 수동 입고. 입고 시 본사 재고 증가. 본사 전용.
 */
class LogisticsInboundController extends Controller
{
    use ResolvesSeller;

    public function __construct(private HqStockService $stock)
    {
    }

    /** GET /api/v1/seller/logistics/inbound?status=all|pending|done */
    public function index(Request $request): JsonResponse
    {
        $this->hq($request);
        $filter = $request->query('status', 'all');

        $q = SupplierStatement::with('supplier')->latest('id');
        if ($filter === 'pending') {
            $q->whereNull('received_at');
        } elseif ($filter === 'done') {
            $q->whereNotNull('received_at');
        }
        $statements = $q->paginate(20);

        $products = SupplyProduct::where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'unit', 'code'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'unit' => $p->unit, 'code' => $p->code]);

        return response()->json([
            'data' => $statements->getCollection()->map(fn (SupplierStatement $s) => $this->row($s))->values(),
            'meta' => [
                'status' => $filter,
                'current_page' => $statements->currentPage(),
                'last_page' => $statements->lastPage(),
                'total' => $statements->total(),
                'products' => $products,
            ],
        ]);
    }

    /** POST /api/v1/seller/logistics/inbound/{statement}/receive */
    public function receive(Request $request, SupplierStatement $statement): JsonResponse
    {
        $this->hq($request);

        if ($statement->received_at) {
            return response()->json(['message' => '이미 입고 처리된 명세서입니다.'], 409);
        }

        foreach (($statement->items ?? []) as $it) {
            $pid = (int) ($it['product_id'] ?? 0);
            $qty = (int) ($it['qty'] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $this->stock->inbound($pid, $it['name'] ?? '', $qty, 'statement', 'SupplierStatement',
                    $statement->id, $request->user()->id, '명세서 '.$statement->statement_no);
            }
        }

        $statement->update(['received_at' => now(), 'received_by' => $request->user()->id]);

        return response()->json([
            'message' => "명세서 {$statement->statement_no} 입고 완료 — 본사 재고에 반영했습니다.",
            'data' => $this->row($statement->fresh('supplier')),
        ]);
    }

    /** POST /api/v1/seller/logistics/inbound/manual  body: { supply_product_id, qty, note? } */
    public function manual(Request $request): JsonResponse
    {
        $this->hq($request);

        $data = $request->validate([
            'supply_product_id' => ['required', 'exists:supply_products,id'],
            'qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $p = SupplyProduct::findOrFail($data['supply_product_id']);
        $this->stock->inbound($p->id, $p->name, (int) $data['qty'], 'manual', null, null,
            $request->user()->id, $data['note'] ?? '수동 입고');

        return response()->json(['message' => "{$p->name} {$data['qty']}{$p->unit} 입고했습니다."]);
    }

    private function hq(Request $request): void
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403);
    }

    private function row(SupplierStatement $s): array
    {
        return [
            'id' => $s->id,
            'statement_no' => $s->statement_no,
            'supplier_name' => $s->supplier?->name ?? $s->supplier_name,
            'item_count' => (int) $s->item_count,
            'total' => (int) $s->total,
            'received' => $s->received_at !== null,
            'received_at' => $s->received_at?->format('Y-m-d H:i'),
            'created_at' => $s->created_at?->format('Y-m-d H:i'),
            'items' => collect($s->items ?? [])->map(fn ($it) => [
                'name' => $it['name'] ?? '',
                'qty' => (int) ($it['qty'] ?? 0),
                'unit' => $it['unit'] ?? '',
            ])->values(),
        ];
    }
}
