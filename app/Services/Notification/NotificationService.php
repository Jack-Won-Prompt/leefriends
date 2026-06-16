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

    /** @param \Illuminate\Support\Collection|User[] $users */
    public function notifyUsers($users, string $type, string $title, string $body, array $data = []): void
    {
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
    }
}
