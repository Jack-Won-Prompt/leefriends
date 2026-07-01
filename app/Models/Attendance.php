<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 출퇴근 기록.
 */
class Attendance extends Model
{
    protected $fillable = [
        'user_id', 'role', 'store_id', 'supplier_id',
        'work_date', 'clock_in_at', 'clock_out_at',
        'status', 'approved_by', 'approved_at', 'note',
    ];

    protected $casts = [
        'work_date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public const STATUS_LABELS = [
        'pending' => '승인대기',
        'approved' => '승인',
        'rejected' => '반려',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** 근무시간(시) — 퇴근 미기록이면 0 */
    public function hours(): float
    {
        if (! $this->clock_out_at) {
            return 0.0;
        }

        return round($this->clock_in_at->diffInMinutes($this->clock_out_at) / 60, 2);
    }

    /** 일당 = 근무시간 × 시급 */
    public function wage(): int
    {
        return (int) round($this->hours() * (int) ($this->user->hourly_wage ?? 0));
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isOpen(): bool
    {
        return is_null($this->clock_out_at);
    }

    /** 로그인 사용자의 소속(역할+조직) 스코프 */
    public function scopeForOrg($q, User $user)
    {
        $q->where('role', $user->role);
        if ($user->role === 'store') {
            $q->where('store_id', $user->store_id);
        } elseif ($user->role === 'supplier') {
            $q->where('supplier_id', $user->supplier_id);
        }

        return $q;
    }
}
