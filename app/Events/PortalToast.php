<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 인앱 알림센터에는 적재하지 않는 일회성 웹 토스트 (예: 채팅 메시지 도착).
 * 레이아웃의 토스트 핸들러가 동일 채널/이벤트를 수신한다(id 없으면 벨 카운트는 증가 안 함).
 */
class PortalToast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public string $title,
        public string $body,
        public string $type = 'toast',
        public array $extra = [],
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('portal.user.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'app.notification';
    }

    public function broadcastWith(): array
    {
        return array_merge([
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
        ], $this->extra);
    }
}
