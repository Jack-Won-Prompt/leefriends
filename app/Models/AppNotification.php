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

    /** 알림 유형 라벨 (웹 포털 «FCM 알림 이력»·앱 공용) */
    public const TYPE_LABELS = [
        'product_new' => '신규 상품',
        'product_updated' => '상품 수정',
        'order_created' => '발주 접수',
        'order_updated' => '발주 변경',
        'order_canceled' => '발주 취소',
        'order_delivered' => '배송 완료',
        'order_item_added' => '발주품목 추가',
        'order_item_removed' => '발주품목 삭제',
        'order_item_updated' => '발주품목 변경',
        'shipment_confirmed' => '출고 확정',
        'restock' => '재입고',
        'statement' => '거래명세서',
        'tax_invoice_issued' => '세금계산서',
        'portal_notice' => '공지사항',
        'franchise_inquiry' => '창업 문의',
        'attendance' => '근태',
        'leave' => '휴무',
        'general' => '일반',
    ];

    public static function typeLabel(?string $type): string
    {
        return self::TYPE_LABELS[$type] ?? (string) $type;
    }

    /**
     * 알림 이력 조회 조건 정규화 — 기본 기간은 최근 7일 (웹 포털·앱 공용).
     *
     * @return array{role: string, store: ?string, type: ?string, from: string, to: string, q: string}
     */
    public static function logFilters(array $input): array
    {
        return [
            'role' => (string) ($input['role'] ?? 'all'),                 // all | hq | store
            'store' => ($input['store'] ?? null) ?: null,
            'type' => ($input['type'] ?? null) ?: null,
            'from' => ($input['from'] ?? null) ?: now()->subDays(7)->toDateString(),
            'to' => ($input['to'] ?? null) ?: today()->toDateString(),
            'q' => trim((string) ($input['q'] ?? '')),
        ];
    }

    /** 알림 이력: 수신자(users)·매장(stores) 조인 + 조건 필터. 본사·매장 수신분만. */
    public function scopeLogFilter($query, array $f)
    {
        return $query
            ->join('users', 'users.id', '=', 'app_notifications.user_id')
            ->leftJoin('stores', 'stores.id', '=', 'users.store_id')
            ->whereIn('users.role', ['hq', 'store'])
            ->when(in_array($f['role'], ['hq', 'store'], true), fn ($w) => $w->where('users.role', $f['role']))
            ->when($f['store'], fn ($w) => $w->where('users.store_id', $f['store']))
            ->when($f['type'], fn ($w) => $w->where('app_notifications.type', $f['type']))
            ->when($f['from'], fn ($w) => $w->whereDate('app_notifications.created_at', '>=', $f['from']))
            ->when($f['to'], fn ($w) => $w->whereDate('app_notifications.created_at', '<=', $f['to']))
            ->when($f['q'] !== '', fn ($w) => $w->where(function ($s) use ($f) {
                $s->where('app_notifications.title', 'like', "%{$f['q']}%")
                    ->orWhere('app_notifications.body', 'like', "%{$f['q']}%");
            }));
    }
}
