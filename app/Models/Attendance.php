<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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

    /**
     * 근무일 + 출근/퇴근 시각(H:i)을 타임스탬프로 변환.
     * 퇴근 시각이 출근 시각보다 이르면(예: 20:00 출근 → 01:00 퇴근) 자정을 넘긴 야간 근무로 보고
     * 퇴근을 다음 날로 처리한다. work_date 는 출근일 기준으로 유지된다.
     *
     * @return array{0: Carbon, 1: Carbon|null} [출근, 퇴근|null]
     */
    public static function resolveTimes(string $workDate, string $clockIn, ?string $clockOut): array
    {
        $in = Carbon::parse($workDate.' '.$clockIn);
        $out = null;
        if (! empty($clockOut)) {
            $out = Carbon::parse($workDate.' '.$clockOut);
            if ($out->lessThan($in)) {
                $out->addDay();
            }
        }

        return [$in, $out];
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

    /** 지각 분: 표준 출근시각보다 늦게 출근한 분 (정직원 급여 재계산용) */
    public function lateMinutes(): int
    {
        if (! $this->clock_in_at || ! $this->user) {
            return 0;
        }
        $std = $this->workDateAt($this->user->workStart());
        $actual = ($this->clock_in_at->hour * 60) + $this->clock_in_at->minute;

        return max(0, $actual - $std);
    }

    /** 오버타임 분: 표준 퇴근시각 이후 근무한 분 (정직원 급여 재계산용) */
    public function overtimeMinutes(): int
    {
        if (! $this->clock_out_at || ! $this->user) {
            return 0;
        }
        $std = $this->workDateAt($this->user->workEnd());
        $actual = ($this->clock_out_at->hour * 60) + $this->clock_out_at->minute;

        return max(0, $actual - $std);
    }

    /** 'HH:MM' → 그 날의 분(자정 기준) */
    private function workDateAt(string $hm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hm) + [1 => 0]);

        return $h * 60 + $m;
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
