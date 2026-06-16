<?php

namespace App\Services\Fulfillment;

use App\Models\Order;
use App\Models\OrderChange;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Collection;

/**
 * 매장 주문 수정/삭제 시 영향받는 판매자(본사/공급처)에 변경 이벤트 기록 + 알림.
 */
class OrderChangeService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * @param  Collection|null  $itemsSnapshot  취소 전 품목 스냅샷 (없으면 현재 품목)
     */
    public function record(Order $order, string $type, ?Collection $itemsSnapshot = null): void
    {
        $items = $itemsSnapshot ?? $order->items()->get();
        $store = $order->store;
        // 알림 노출용 매장명: 브랜드 접두어(리프렌즈) 제거
        $storeName = trim(str_replace(['리프렌즈', '리프랜즈'], '', $store->name ?? '')) ?: '매장';
        $typeLabel = OrderChange::TYPES[$type] ?? $type;

        // 영향받는 판매자 집합
        $sellers = [];
        foreach ($items as $it) {
            if ($it->supply_type === 'supplier' && $it->supplier_id) {
                $sellers['s' . $it->supplier_id] = ['hq' => false, 'sid' => (int) $it->supplier_id];
            } else {
                $sellers['hq'] = ['hq' => true, 'sid' => null];
            }
        }

        foreach ($sellers as $seller) {
            OrderChange::create([
                'order_id' => $order->id,
                'store_id' => $order->store_id,
                'change_type' => $type,
                'seller_type' => $seller['hq'] ? 'hq' : 'supplier',
                'supplier_id' => $seller['sid'],
                'order_no' => $order->order_no,
                'summary' => "{$storeName}의 발주 {$order->order_no}이(가) {$typeLabel}되었습니다.",
            ]);

            $users = $seller['hq']
                ? User::where('role', 'hq')->get()
                : User::where('role', 'supplier')->where('supplier_id', $seller['sid'])->get();

            $this->notifications->notifyUsers(
                $users,
                'order_' . $type,
                $type === 'canceled' ? '⚠️ 매장 주문 취소' : '✏️ 매장 주문 수정',
                "{$storeName} · {$order->order_no} 주문이 {$typeLabel}되었습니다. 화면을 확인 후 반영하세요.",
                ['order_id' => $order->id, 'order_no' => $order->order_no, 'change_type' => $type],
            );
        }
    }
}
