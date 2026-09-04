<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use Illuminate\Console\Command;

/**
 * 출퇴근 시간 데이터 감사 (읽기 전용).
 * - 역전: 퇴근 시각이 출근 시각보다 이르거나 같음(근무시간 ≤ 0) — 잘못된 데이터
 * - 과다: 근무시간이 임계(기본 16h) 초과 — 오입력/오계산 의심
 * - 야간: 자정을 넘긴 정상 야간 근무(참고 목록)
 *
 * 운영 서버에서:  php artisan attendance:audit
 */
class AuditAttendanceTimes extends Command
{
    protected $signature = 'attendance:audit {--max-hours=16 : 과다 근무 경고 임계(시간)}';

    protected $description = '출퇴근 시간 데이터 이상 여부를 점검(읽기 전용)';

    public function handle(): int
    {
        $maxHours = (float) $this->option('max-hours');

        $total = Attendance::count();
        $done = Attendance::whereNotNull('clock_out_at')->with('user')->get();

        $line = fn (Attendance $a) => sprintf(
            '  #%d %s | %s → %s = %sh',
            $a->id,
            $a->user->name ?? ('u'.$a->user_id),
            $a->clock_in_at->format('Y-m-d H:i'),
            $a->clock_out_at->format('Y-m-d H:i'),
            $a->hours()
        );

        // 1) 역전 — 퇴근이 출근보다 이르거나 같음 (근무시간 ≤ 0)
        $bad = $done->filter(fn ($a) => $a->clock_out_at->lessThanOrEqualTo($a->clock_in_at));
        // 2) 과다 — 임계 초과
        $long = $done->filter(fn ($a) => $a->clock_in_at->diffInMinutes($a->clock_out_at) / 60 > $maxHours);
        // 3) 야간 — 자정을 넘긴 근무 (정상, 참고)
        $overnight = $done->filter(fn ($a) => $a->clock_out_at->toDateString() !== $a->clock_in_at->toDateString());

        $this->info(sprintf('출퇴근 감사 — 총 %d건 · 퇴근기록 %d건 (임계 %sh)', $total, $done->count(), $maxHours));
        $this->newLine();

        $this->line(sprintf('[이상] 역전(퇴근 ≤ 출근): %d건', $bad->count()));
        $bad->each(fn ($a) => $this->line($line($a)));

        $this->line(sprintf('[점검] %sh 초과: %d건', $maxHours, $long->count()));
        $long->each(fn ($a) => $this->line($line($a)));

        $this->line(sprintf('[정상] 자정 넘긴 야간 근무: %d건', $overnight->count()));
        $overnight->take(50)->each(fn ($a) => $this->line($line($a)));

        $this->newLine();
        if ($bad->isEmpty()) {
            $this->info('✔ 역전(음수 근무시간) 데이터 없음 — 시간 데이터 정상.');

            return self::SUCCESS;
        }

        $this->error(sprintf('[주의] 역전 데이터 %d건 발견 — 확인/보정이 필요합니다.', $bad->count()));

        return self::FAILURE;
    }
}
