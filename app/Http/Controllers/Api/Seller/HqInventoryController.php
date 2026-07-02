<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\HqInventory;
use App\Models\HqInventoryMovement;
use App\Models\SupplyProduct;
use App\Services\Inventory\HqStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 모바일 앱 — 본사 재고/물류 관리 (본사 창고 재고 조회·조정·수동입고·기본셋팅·입고알림).
 * 웹 Portal\Hq\HqInventoryController + LogisticsInboundController(manual) 와 동일 로직. 본사(hq) 전용.
 */
class HqInventoryController extends Controller
{
    use ResolvesSeller;

    public function __construct(private HqStockService $stock)
    {
    }

    private function guardHq(Request $request): void
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');
    }

    /** GET — 재고 목록 (품목별 실물/예약/가용) + 최근 이동 */
    public function index(Request $request): JsonResponse
    {
        $this->guardHq($request);
        $keyword = trim((string) $request->query('q', ''));
        $only = $request->query('only', 'all'); // all | managed | shortage

        $q = SupplyProduct::where('is_active', true)
            ->leftJoin('hq_inventories', 'hq_inventories.supply_product_id', '=', 'supply_products.id')
            ->select('supply_products.id', 'supply_products.name', 'supply_products.code', 'supply_products.unit',
                'supply_products.supply_type', 'supply_products.image',
                'hq_inventories.id as inv_id', 'hq_inventories.qty', 'hq_inventories.reserved_qty')
            ->orderBy('supply_products.name');

        if ($keyword !== '') {
            $q->where(fn ($w) => $w->where('supply_products.name', 'like', "%{$keyword}%")
                ->orWhere('supply_products.code', 'like', "%{$keyword}%"));
        }
        if ($only === 'managed') {
            $q->whereNotNull('hq_inventories.id');
        } elseif ($only === 'shortage') {
            $q->whereNotNull('hq_inventories.id')
                ->whereRaw('hq_inventories.qty - hq_inventories.reserved_qty <= 0');
        }

        $rows = $q->paginate(30);

        return response()->json([
            'data' => $rows->getCollection()->map(function ($r) {
                $qty = $r->inv_id ? (int) $r->qty : null;
                $reserved = $r->inv_id ? (int) $r->reserved_qty : 0;

                return [
                    'product_id' => (int) $r->id,
                    'name' => $r->name,
                    'code' => $r->code,
                    'unit' => $r->unit,
                    'image' => $r->image ? asset($r->image) : null,
                    'managed' => (bool) $r->inv_id,
                    'qty' => $qty,
                    'reserved' => $reserved,
                    'available' => $qty === null ? null : $qty - $reserved,
                ];
            })->values(),
            'meta' => [
                'only' => $only,
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'total' => $rows->total(),
            ],
            'recent' => HqInventoryMovement::latest('id')->limit(15)->get()->map(fn ($m) => [
                'id' => $m->id,
                'product_name' => $m->product_name,
                'type' => $m->type,
                'delta' => (int) $m->qty_delta,
                'note' => $m->note,
                'created_at' => $m->created_at?->format('Y-m-d H:i'),
            ])->all(),
        ]);
    }

    /** POST — 실사 수량 조정(목표값으로 설정) */
    public function adjust(Request $request): JsonResponse
    {
        $this->guardHq($request);
        $data = $request->validate([
            'supply_product_id' => ['required', 'exists:supply_products,id'],
            'qty' => ['required', 'integer', 'min:0', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);
        $p = SupplyProduct::findOrFail($data['supply_product_id']);
        $this->stock->adjust($p->id, $p->name, (int) $data['qty'], $request->user()->id, $data['note'] ?? '실사 조정');

        return response()->json(['message' => "{$p->name} 재고를 {$data['qty']}{$p->unit}로 조정했습니다."]);
    }

    /** POST — 수동 입고(수량 가산) */
    public function inbound(Request $request): JsonResponse
    {
        $this->guardHq($request);
        $data = $request->validate([
            'supply_product_id' => ['required', 'exists:supply_products,id'],
            'qty' => ['required', 'integer', 'min:1', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);
        $p = SupplyProduct::findOrFail($data['supply_product_id']);
        $this->stock->inbound($p->id, $p->name, (int) $data['qty'], 'manual', null, null, $request->user()->id, $data['note'] ?? '수동 입고');

        return response()->json(['message' => "{$p->name} {$data['qty']}{$p->unit} 입고했습니다."]);
    }

    /** POST — 기본재고 셋팅(재고 없는 품목 기본 10개) */
    public function seed(Request $request): JsonResponse
    {
        $this->guardHq($request);
        $base = 10;
        $targets = SupplyProduct::where('is_active', true)
            ->leftJoin('hq_inventories', 'hq_inventories.supply_product_id', '=', 'supply_products.id')
            ->where(fn ($w) => $w->whereNull('hq_inventories.id')->orWhere('hq_inventories.qty', '<=', 0))
            ->select('supply_products.id', 'supply_products.name')->get();
        foreach ($targets as $p) {
            $this->stock->adjust($p->id, $p->name, $base, $request->user()->id, "기본재고 셋팅({$base})");
        }

        return response()->json(['message' => $targets->count()."개 품목에 기본재고 {$base}개를 설정했습니다."]);
    }

    /** POST — 단일 품목 기본재고 셋팅 */
    public function seedOne(Request $request, SupplyProduct $product): JsonResponse
    {
        $this->guardHq($request);
        $this->stock->adjust($product->id, $product->name, 10, $request->user()->id, '기본재고 셋팅(10)');

        return response()->json(['message' => "{$product->name} 기본재고 10개를 설정했습니다."]);
    }

    /** POST — 재고 입고 알림(전 매장 FCM+SMS) */
    public function notifyRestock(
        Request $request,
        SupplyProduct $product,
        \App\Services\Notification\NotificationService $notify,
        \App\Services\Popbill\PopbillMessagingService $messaging
    ): JsonResponse {
        $this->guardHq($request);
        $inv = HqInventory::where('supply_product_id', $product->id)->first();
        if (! $inv || $inv->qty <= 0) {
            return response()->json(['message' => '재고가 있는 품목만 입고 알림을 보낼 수 있습니다.'], 422);
        }

        $title = '📦 재고 입고 안내';
        $body = "본사 {$product->name} 품목 재고가 입고되었습니다.";
        $storeUsers = \App\Models\User::where('role', 'store')->get();
        $notify->notifyUsers($storeUsers, 'restock', $title, $body, ['product_id' => $product->id]);

        $corp = preg_replace('/\D/', '', (string) config('popbill.sms.corp_num'));
        $stores = \App\Models\Store::where('is_active', true)->whereNotNull('phone')->get();
        defer(function () use ($messaging, $corp, $stores, $body) {
            foreach ($stores as $s) {
                try {
                    $messaging->send($corp, $s->phone, '재고 입고 안내', $body, $s->name);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('재고입고 SMS 실패', ['store_id' => $s->id, 'error' => $e->getMessage()]);
                }
            }
        });

        return response()->json(['message' => "«{$product->name}» 재고 입고 알림을 전 매장에 전송했습니다."]);
    }
}
