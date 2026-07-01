<?php

namespace App\Services\Notification;

use App\Models\AppNotification;
use App\Models\Store;
use App\Models\User;
use App\Services\Fcm\FcmService;

/**
 * 인앱 알림 저장 + FCM 푸시 전송을 함께 처리.
 */
class NotificationService
{
    public function __construct(private FcmService $fcm)
    {
    }

    /** 특정 매장 소속 사용자 전원에게 알림 */
    public function notifyStore(int $storeId, string $type, string $title, string $body, array $data = []): void
    {
        $users = User::where('store_id', $storeId)->where('role', 'store')->get();
        $this->notifyUsers($users, $type, $title, $body, $data);
    }

    /**
     * 새 발주 접수 → 본사 전원 + 해당 발주에 포함된 공급처에 알림.
     */
    public function notifyNewOrder(\App\Models\Order $order): void
    {
        $order->loadMissing(['items', 'store']);

        $storeName = $order->store?->name ?? '매장';
        $title = '🧾 새 발주가 접수되었습니다';
        $body = "{$storeName} · {$order->order_no} ({$order->items->count()}품목)";
        $data = ['order_id' => $order->id, 'order_no' => $order->order_no];

        // 본사 (전체 발주 관할)
        $this->notifyUsers(
            User::where('role', 'hq')->get(),
            'order_created', $title, $body, $data,
        );

        // 해당 발주에 품목이 있는 공급처
        $supplierIds = $order->items
            ->where('supply_type', 'supplier')
            ->pluck('supplier_id')
            ->filter()
            ->unique();

        foreach ($supplierIds as $supplierId) {
            $this->notifyUsers(
                User::where('role', 'supplier')->where('supplier_id', $supplierId)->get(),
                'order_created', $title, $body, $data,
            );
        }
    }

    /** @param \Illuminate\Support\Collection|User[] $users */
    public function notifyUsers($users, string $type, string $title, string $body, array $data = []): void
    {
        // 인앱 알림 저장 + 브로드캐스트(Pusher) + FCM 푸시를 응답 이후로 미뤄
        // 화면 동작(예: 출근 버튼)이 네트워크 전송을 기다리지 않도록 한다.
        defer(function () use ($users, $type, $title, $body, $data) {
            $tokens = [];
            foreach ($users as $user) {
                AppNotification::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                ]);
                $tokens = array_merge($tokens, $user->deviceTokens()->pluck('token')->all());
            }

            if ($tokens) {
                $this->fcm->sendToTokens($tokens, $title, $body, array_merge($data, ['type' => $type]));
            }
        });
    }
}
