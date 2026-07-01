<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 계좌조회 수집 작업 이력.
 */
class BankCollectJob extends Model
{
    protected $fillable = [
        'corp_num', 'bank_code', 'account_number', 'start_date', 'end_date',
        'job_id', 'job_state', 'collect_count', 'error_code', 'error_reason', 'imported_at', 'requested_by',
    ];

    protected $casts = [
        'job_state' => 'integer',
        'collect_count' => 'integer',
        'error_code' => 'integer',
        'imported_at' => 'datetime',
    ];

    public const STATE_LABELS = [
        1 => '수집 대기',
        2 => '수집 중',
        3 => '수집 완료',
    ];

    public function isDone(): bool
    {
        return $this->job_state === 3;
    }

    public function stateLabel(): string
    {
        return self::STATE_LABELS[$this->job_state] ?? '알 수 없음';
    }
}
