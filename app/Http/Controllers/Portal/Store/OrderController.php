<?php

namespace App\Http\Controllers\Portal\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SalesOrder;
use App\Models\SupplyProduct;
use App\Services\Fulfillment\OrderChangeService;
use App\Services\Fulfillment\SalesOrderGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('store_id', Auth::user()->store_id)
            ->where('order_type', 'normal')
            ->withCount('items')
            ->latest()
            ->paginate(15);

        return view('portal.store.orders.index', compact('orders'));
    }

    /** 샘플 주문 목록 */
    public function sampleIndex()
    {
        $orders = Order::where('store_id', Auth::user()->store_id)
            ->where('order_type', 'sample')
            ->withCount('items')
            ->latest()
            ->paginate(15);

        return view('portal.store.orders.sample_index', compact('orders'));
    }

    public function create()
    {
        return view('portal.store.orders.create', [
            'products' => $this->productList(),
            'editOrder' => null,
            'prefill' => [],
            'pastOrders' => $this->recentOrders(null, 'normal'),
            'orderType' => 'normal',
        ]);
    }

    /** 샘플 주문하기 (가격 미표시) */
    public function sampleCreate()
    {
        return view('portal.store.orders.create', [
            'products' => $this->productList(),
            'editOrder' => null,
            'prefill' => [],
            'pastOrders' => $this->recentOrders(null, 'sample'),
            'orderType' => 'sample',
        ]);
    }

    public function store(Request $request, \App\Services\Notification\NotificationService $notifications)
    {
        $user = Auth::user();
        abort_unless($user->store_id, 403, '연결된 매장이 없습니다.');

        $data = $this->validateOrder($request);
        $type = in_array($request->input('order_type'), ['normal', 'sample'], true) ? $request->input('order_type') : 'normal';
        $selected = collect($data['qty'])->filter(fn ($q) => (int) $q > 0);
        if ($selected->isEmpty()) {
            return back()->withErrors(['qty' => '발주할 품목의 수량을 입력해 주세요.'])->withInput();
        }

        $order = DB::transaction(function () use ($user, $selected, $data, $type) {
            $order = Order::create([
                'order_no' => $this->generateOrderNo($type),
                'store_id' => $user->store_id,
                'user_id' => $user->id,
                'status' => 'pending',
                'order_type' => $type,
                'note' => $data['note'] ?? null,
            ]);
            $this->buildItems($order, $selected, collect($data['unit'] ?? []), $type === 'sample');
            (new SalesOrderGenerator())->generate($order);

            return $order;
        });

        // 본사 + 해당 공급처에 새 발주 알림(FCM)
        $notifications->notifyNewOrder($order);

        if ($type === 'sample') {
            return redirect()->route('portal.store.orders.show', $order)
                ->with('success', '샘플 주문이 접수되었습니다. 본사·공급처에서 확인합니다.');
        }

        return redirect()->route('portal.store.orders.show', $order)
            ->with('success', '발주가 접수되었습니다. 공급처 품목은 해당 공급처로 동시에 전달되었습니다.');
    }

    public function edit(Order $order)
    {
        $this->authorizeEditable($order);

        $prefill = $order->items->mapWithKeys(fn ($it) => [
            $it->supply_product_id => ['qty' => $it->qty, 'unit_id' => $it->supply_product_unit_id],
        ])->all();

        return view('portal.store.orders.create', [
            'products' => $this->productList(),
            'editOrder' => $order,
            'prefill' => $prefill,
            'pastOrders' => $this->recentOrders($order->id),
        ]);
    }

    public function update(Request $request, Order $order, OrderChangeService $changes)
    {
        $this->authorizeEditable($order);

        $data = $this->validateOrder($request);
        $selected = collect($data['qty'])->filter(fn ($q) => (int) $q > 0);
        if ($selected->isEmpty()) {
            return back()->withErrors(['qty' => '발주할 품목의 수량을 입력해 주세요.'])->withInput();
        }

        $oldItems = $order->items()->get();

        DB::transaction(function () use ($order, $selected, $data) {
            // 기존 판매주문/품목 제거 후 재생성 (미출고 상태에서만 허용)
            SalesOrder::where('order_id', $order->id)->delete();
            $order->items()->delete();
            $order->update(['status' => 'pending', 'note' => $data['note'] ?? null]);

            $this->buildItems($order, $selected, collect($data['unit'] ?? []));
            (new SalesOrderGenerator())->generate($order);
        });

        // 영향받는 본사/공급처에 변경 알림 + 미반영 기록
        $changes->record($order, 'updated', $oldItems);

        return redirect()->route('portal.store.orders.show', $order)
            ->with('success', '발주가 수정되었습니다. 본사·공급처에 변경 알림이 전송되었습니다.');
    }

    public function destroy(Order $order, OrderChangeService $changes)
    {
        $this->authorizeEditable($order);

        $snapshot = $order->items()->get();

        DB::transaction(function () use ($order) {
            SalesOrder::where('order_id', $order->id)->update(['status' => 'canceled']);
            $order->update(['status' => 'canceled']);
        });

        $changes->record($order, 'canceled', $snapshot);

        return redirect()->route('portal.store.orders.index')
            ->with('success', '발주가 취소되었습니다. 본사·공급처에 취소 알림이 전송되었습니다.');
    }

    public function show(Order $order)
    {
        abort_unless($order->store_id === Auth::user()->store_id, 403);
        $order->load('items');

        return view('portal.store.orders.show', [
            'order' => $order,
            'editable' => $this->isEditable($order),
        ]);
    }

    /** 거래명세서 — 본사/공급처별로 묶어 출력 */
    public function statement(Order $order)
    {
        abort_unless($order->store_id === Auth::user()->store_id, 403);
        abort_if($order->isSample(), 404, '샘플 주문은 거래명세서를 제공하지 않습니다.');
        $order->load(['items', 'store']);

        $groups = $order->items->groupBy(fn ($it) => $it->supply_type === 'supplier' ? 'supplier:'.$it->supplier_id : 'hq');

        return view('portal.store.orders.statement', compact('order', 'groups'));
    }

    /* ----------------- helpers ----------------- */

    private function productList()
    {
        return SupplyProduct::active()->approved()
            ->with(['supplier', 'units'])
            ->catalogOrder()
            ->get()
            ->groupBy('category');
    }

    /** 빠른 재발주용 최근 발주 이력 (취소 제외, 최신 10건) */
    private function recentOrders(?int $excludeId = null, string $type = 'normal')
    {
        return Order::where('store_id', Auth::user()->store_id)
            ->where('order_type', $type)
            ->where('status', '!=', 'canceled')
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->with('items')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_no' => $o->order_no,
                'date' => $o->created_at->format('Y-m-d'),
                'count' => $o->items->count(),
                'amount' => (int) $o->store_amount,
                'items' => $o->items->map(fn ($it) => [
                    'pid' => $it->supply_product_id,
                    'unitId' => $it->supply_product_unit_id,
                    'qty' => (int) $it->qty,
                ])->values(),
            ])->values();
    }

    private function validateOrder(Request $request): array
    {
        return $request->validate([
            'qty' => ['required', 'array'],
            'qty.*' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'unit' => ['array'],
            'unit.*' => ['nullable', 'integer'],
            'order_type' => ['nullable', 'in:normal,sample'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function buildItems(Order $order, $selected, $units, bool $isSample = false): void
    {
        $products = SupplyProduct::active()->approved()->whereIn('id', $selected->keys())->with(['supplier', 'units'])->get()->keyBy('id');
        $storeTotal = 0;
        $supplyTotal = 0;

        foreach ($selected as $pid => $qty) {
            $p = $products[$pid] ?? null;
            if (! $p) {
                continue;
            }
            $qty = (int) $qty;
            $unitId = (int) $units->get($pid);
            $unit = $p->units->firstWhere('id', $unitId) ?? $p->units->firstWhere('is_default', true) ?? $p->units->first();

            // 샘플 주문은 가격을 노출/청구하지 않으므로 단가·금액 0 처리
            $storePrice = $isSample ? 0 : ($unit->store_price ?? $p->store_price);
            $supplyPrice = $isSample ? 0 : ($p->supply_type === 'supplier' ? ($unit->supply_price ?? $p->supply_price) : 0);
            $storeLine = $storePrice * $qty;
            $supplyLine = $supplyPrice * $qty;

            OrderItem::create([
                'order_id' => $order->id,
                'supply_product_id' => $p->id,
                'supply_product_unit_id' => $unit->id ?? null,
                'product_name' => $p->name,
                'unit' => $unit->name ?? $p->unit,
                'supply_type' => $p->supply_type,
                'supplier_id' => $p->supply_type === 'supplier' ? $p->supplier_id : null,
                'supplier_name' => $p->supply_type === 'supplier' ? optional($p->supplier)->name : '본사',
                'qty' => $qty,
                'store_unit_price' => $storePrice,
                'supply_unit_price' => $supplyPrice,
                'store_line_amount' => $storeLine,
                'supply_line_amount' => $supplyLine,
                'fulfillment_status' => 'pending',
            ]);

            $storeTotal += $storeLine;
            $supplyTotal += $supplyLine;
        }

        $order->update(['store_amount' => $storeTotal, 'supply_amount' => $supplyTotal]);
    }

    /** 수정/취소 가능: 본인 매장 + 미취소 + 출고 전 (어떤 품목도 출고에 묶이지 않음) */
    private function isEditable(Order $order): bool
    {
        return $order->store_id === Auth::user()->store_id
            && ! in_array($order->status, ['canceled', 'completed'], true)
            && ! $order->items()->whereNotNull('shipment_id')->exists();
    }

    private function authorizeEditable(Order $order): void
    {
        abort_unless($order->store_id === Auth::user()->store_id, 403);
        abort_unless($this->isEditable($order), 400, '이미 출고가 진행된 주문은 수정/취소할 수 없습니다.');
        $order->loadMissing('items');
    }

    private function generateOrderNo(string $type = 'normal'): string
    {
        $date = now()->format('Ymd');
        $prefix = $type === 'sample' ? 'SP' : 'PO';
        $seq = Order::whereDate('created_at', today())->count() + 1;

        return sprintf('%s-%s-%03d', $prefix, $date, $seq);
    }
}
