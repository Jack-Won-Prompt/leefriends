<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 홈택스 세금계산서 수집 작업 이력.
 */
class HometaxCollectJob extends Model
{
    protected $fillable = [
        'corp_num', 'ti_type', 'date_type', 'start_date', 'end_date',
        'job_id', 'job_state', 'collect_count', 'error_code', 'error_reason', 'requested_by',
    ];

    protected $casts = [
        'job_state' => 'integer',
        'collect_count' => 'integer',
        'error_code' => 'integer',
    ];

    public const TYPE_SELL = 'SELL'; // 매출
    public const TYPE_BUY = 'BUY';   // 매입

    public const STATE_LABELS = [
        1 => '수집 대기',
        2 => '수집 중',
        3 => '수집 완료',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isDone(): bool
    {
        return $this->job_state === 3;
    }

    public function stateLabel(): string
    {
        return self::STATE_LABELS[$this->job_state] ?? '알 수 없음';
    }

    public function typeLabel(): string
    {
        return $this->ti_type === self::TYPE_BUY ? '매입' : '매출';
    }
}
