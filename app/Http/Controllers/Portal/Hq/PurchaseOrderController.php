<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplyProduct;
use App\Models\User;
use App\Services\Inventory\HqStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** 본사 → 공급처 구매(매입) 발주 */
class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $supplier = $request->query('supplier', 'all');
        $status = $request->query('status', 'all');

        $query = PurchaseOrder::with('supplier')->latest();
        if ($supplier !== 'all' && is_numeric($supplier)) {
            $query->where('supplier_id', (int) $supplier);
        }
        if (array_key_exists($status, PurchaseOrder::STATUSES)) {
            $query->where('status', $status);
        }

        return view('portal.hq.purchase_orders.index', [
            'orders' => $query->paginate(20)->withQueryString(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'supplier' => $supplier,
            'status' => $status,
        ]);
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get(['id', 'name']);
        $products = SupplyProduct::where('supply_type', 'supplier')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'supplier_id', 'name', 'unit', 'supply_price'])
            ->map(fn ($p) => [
                'id' => $p->id, 'supplier_id' => $p->supplier_id, 'name' => $p->name,
                'unit' => $p->unit, 'price' => (int) $p->supply_price,
            ])->values();

        return view('portal.hq.purchase_orders.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:supply_products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99999'],
        ], ['items.required' => '발주할 품목을 1개 이상 담아주세요.']);

        $supplier = Supplier::findOrFail($data['supplier_id']);
        $products = SupplyProduct::where('supplier_id', $supplier->id)->where('supply_type', 'supplier')
            ->whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');

        $po = DB::transaction(function () use ($data, $supplier, $products) {
            $po = PurchaseOrder::create([
                'po_no' => $this->generatePoNo(),
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'status' => 'ordered',
                'note' => $data['note'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $total = 0;
            foreach ($data['items'] as $it) {
                $p = $products[$it['product_id']] ?? null;
                if (! $p) {
                    continue;
                }
                $price = (int) $p->supply_price;
                $qty = (int) $it['qty'];
                $amount = $price * $qty;
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'supply_product_id' => $p->id,
                    'product_name' => $p->name,
                    'unit' => $p->unit,
                    'qty' => $qty,
                    'unit_price' => $price,
                    'line_amount' => $amount,
                ]);
                $total += $amount;
            }
            $po->update(['total_amount' => $total]);

            return $po;
        });

        // 공급처에 발주 알림
        $this->notifySupplier($supplier->id, '📦 신규 구매발주 도착',
            "본사에서 구매발주 «{$po->po_no}»를 등록했습니다. (".number_format($po->total_amount).'원)',
            ['purchase_order_id' => $po->id]);

        return redirect()->route('portal.hq.purchase_orders.show', $po)->with('success', "구매발주 «{$po->po_no}»를 등록하고 공급처에 전송했습니다.");
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('items', 'supplier', 'creator');

        return view('portal.hq.purchase_orders.show', ['po' => $purchaseOrder]);
    }

    /** 입고 처리 → 본사 재고 반영 */
    public function receive(PurchaseOrder $purchaseOrder, HqStockService $stock)
    {
        if (in_array($purchaseOrder->status, ['received', 'canceled'], true)) {
            return back()->withErrors(['status' => '이미 처리된 발주입니다.']);
        }

        DB::transaction(function () use ($purchaseOrder, $stock) {
            foreach ($purchaseOrder->items as $item) {
                if ($item->supply_product_id) {
                    $stock->inbound(
                        $item->supply_product_id, $item->product_name, (int) $item->qty,
                        'purchase', 'purchase_order', $purchaseOrder->id, Auth::id(),
                        "구매발주 {$purchaseOrder->po_no} 입고"
                    );
                }
                $item->update(['received_qty' => $item->qty]);
            }
            $purchaseOrder->update(['status' => 'received', 'received_at' => now()]);
        });

        return back()->with('success', '입고 처리되었습니다. 본사 재고에 반영했습니다.');
    }

    /** 구매 거래명세서 PDF 미리보기 */
    public function statementPdf(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['items.supplyProduct', 'supplier']);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('portal.print.purchase-order-statement-pdf', ['po' => $purchaseOrder])
            ->setPaper('a4')->stream('구매거래명세서_'.$purchaseOrder->po_no.'.pdf');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status === 'received') {
            return back()->withErrors(['status' => '입고완료된 발주는 취소할 수 없습니다.']);
        }
        $purchaseOrder->update(['status' => 'canceled']);

        return back()->with('success', '구매발주를 취소했습니다.');
    }

    private function generatePoNo(): string
    {
        $date = now()->format('Ymd');
        $seq = PurchaseOrder::whereDate('created_at', today())->count() + 1;

        return sprintf('PU-%s-%03d', $date, $seq);
    }

    private function notifySupplier(int $supplierId, string $title, string $body, array $data): void
    {
        $users = User::where('supplier_id', $supplierId)->where('role', 'supplier')->get();
        app(\App\Services\Notification\NotificationService::class)->notifyUsers($users, 'purchase_order', $title, $body, $data);
    }
}
