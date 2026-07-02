<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\HqInventory;
use App\Models\HqInventoryMovement;
use App\Models\SupplyProduct;
use App\Services\Inventory\HqStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 물류관리 · 재고관리 — 품목별 본사 재고(실물/예약/가용) 조회 + 수량 입력·수정(실사 보정).
 */
class HqInventoryController extends Controller
{
    public function __construct(private HqStockService $stock)
    {
    }

    public function index(Request $request)
    {
        $keyword = trim((string) $request->query('q', ''));
        $only = $request->query('only', 'all'); // all | managed | shortage

        $q = SupplyProduct::where('is_active', true)
            ->leftJoin('hq_inventories', 'hq_inventories.supply_product_id', '=', 'supply_products.id')
            ->select('supply_products.id', 'supply_products.name', 'supply_products.code', 'supply_products.unit',
                'supply_products.supply_type',
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

        $rows = $q->paginate(30)->withQueryString();

        $recent = HqInventoryMovement::latest('id')->limit(15)->get();

        return view('portal.hq.logistics.inventory', compact('rows', 'keyword', 'only', 'recent'));
    }

    /** 기본재고 셋팅 — 재고 없는 품목(미등록/실물 0)에 기본 수량 설정 + 이력 */
    public function seedDefaults(Request $request)
    {
        $base = 10;

        $targets = SupplyProduct::where('is_active', true)
            ->leftJoin('hq_inventories', 'hq_inventories.supply_product_id', '=', 'supply_products.id')
            ->where(fn ($w) => $w->whereNull('hq_inventories.id')->orWhere('hq_inventories.qty', '<=', 0))
            ->select('supply_products.id', 'supply_products.name')
            ->get();

        foreach ($targets as $p) {
            $this->stock->adjust($p->id, $p->name, $base, Auth::id(), "기본재고 셋팅({$base})");
        }

        return back()->with('success', $targets->count()."개 품목에 기본재고 {$base}개를 설정했습니다.");
    }

    /** 매장에 재고 입고 알림 (웹 토스트 + 앱 FCM + SMS) */
    public function notifyRestock(
        SupplyProduct $product,
        \App\Services\Notification\NotificationService $notify,
        \App\Services\Popbill\PopbillMessagingService $messaging
    ) {
        $title = '📦 재고 입고 안내';
        $body = "본사 {$product->name} 품목 재고가 입고되었습니다.";

        // 웹 알림 토스트 + 앱 FCM (모든 매장 사용자) — notifyUsers가 응답 후 처리(defer)
        $storeUsers = \App\Models\User::where('role', 'store')->get();
        $notify->notifyUsers($storeUsers, 'restock', $title, $body, ['product_id' => $product->id]);

        // SMS (매장 전화번호) — 응답 이후 발송
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

        return back()->with('success', "«{$product->name}» 재고 입고 알림을 전 매장에 전송했습니다. (웹·앱·SMS)");
    }

    /** 단일 품목 기본재고 셋팅(기본 10개) */
    public function seedOne(SupplyProduct $product)
    {
        $base = 10;
        $this->stock->adjust($product->id, $product->name, $base, Auth::id(), "기본재고 셋팅({$base})");

        return back()->with('success', "{$product->name} 기본재고 {$base}개를 설정했습니다.");
    }

    /** 실사 수량 입력·수정 (실물 qty를 목표값으로 조정) */
    public function adjust(Request $request)
    {
        $data = $request->validate([
            'supply_product_id' => ['required', 'exists:supply_products,id'],
            'qty' => ['required', 'integer', 'min:0', 'max:1000000'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        $p = SupplyProduct::findOrFail($data['supply_product_id']);
        $this->stock->adjust($p->id, $p->name, (int) $data['qty'], Auth::id(), $data['note'] ?? '실사 조정');

        return back()->with('success', "{$p->name} 재고를 {$data['qty']}{$p->unit}로 조정했습니다.");
    }
}
