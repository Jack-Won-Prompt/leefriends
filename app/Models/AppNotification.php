<?php

namespace App\Models;

use App\Events\NotificationBroadcast;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $fillable = [
        'user_id', 'type', 'title', 'body', 'data', 'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // 알림이 생성되면(어떤 경로든) 해당 사용자 채널로 실시간 토스트 브로드캐스트
        static::created(function (self $notification) {
            try {
                broadcast(new NotificationBroadcast($notification));
            } catch (\Throwable $e) {
                // 브로드캐스트 실패가 알림 저장/요청을 막지 않도록 무시 (로그만)
                report($e);
            }
        });
    }

    public function scopeUnread($q)
    {
        return $q->whereNull('read_at');
    }
}
