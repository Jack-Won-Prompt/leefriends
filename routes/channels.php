<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| 포털 사용자별 비공개 채널 — 본인 채널만 구독 허용.
*/

Broadcast::channel('portal.user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// 채팅 대화방 — 본사는 전체, 매장/공급처는 본인 대화방만 구독 허용
Broadcast::channel('chat.conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = \App\Models\Conversation::find($conversationId);

    return $conversation && $conversation->accessibleBy($user);
});
