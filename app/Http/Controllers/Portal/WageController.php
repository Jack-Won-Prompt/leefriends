<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\WageSettlement;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 아르바이트 급여 — 기간 조회로 일당·합계 확인 + 입금 처리 상태 관리.
 */
class WageController extends Controller
{
    public function __construct(private NotificationService $notify)
    {
    }

    public function index(Request $request)
    {
        $me = Auth::user();
        abort_if($me->isPartTime(), 403);

        $from = $request->query('from') ?: now()->startOfMonth()->format('Y-m-d');
        $to = $request->query('to') ?: now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        // 소속 아르바이트
        $parttimers = User::where('role', $me->role)->where('employment_type', 'part_time')
            ->when($me->role === 'store', fn ($q) => $q->where('store_id', $me->store_id))
            ->when($me->role === 'supplier', fn ($q) => $q->where('supplier_id', $me->supplier_id))
            ->orderBy('name')->get();

        // 승인된 출퇴근(퇴근 기록 있음) 집계
        $rows = [];
        $grandAmount = 0;
        foreach ($parttimers as $u) {
            $atts = Attendance::where('user_id', $u->id)->where('status', 'approved')
                ->whereNotNull('clock_out_at')
                ->whereBetween('work_date', [$from, $to])->get();
            $hours = round($atts->sum(fn ($a) => $a->hours()), 2);
            $amount = (int) $atts->sum(fn ($a) => $a->wage());
            $days = $atts->count();

            $settlement = WageSettlement::where('user_id', $u->id)
                ->whereDate('period_from', $from)->whereDate('period_to', $to)->first();

            $rows[] = [
                'user' => $u,
                'days' => $days,
                'hours' => $hours,
                'amount' => $amount,
                'settlement' => $settlement,
            ];
            $grandAmount += $amount;
        }

        return view('portal.wages.index', [
            'from' => $from,
            'to' => $to,
            'rows' => $rows,
            'grandAmount' => $grandAmount,
        ]);
    }

    /** 입금 처리 → 정산 기록 + 아르바이트 알림 */
    public function pay(Request $request)
    {
        $me = Auth::user();
        abort_if($me->isPartTime(), 403);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
            'hours' => ['required', 'numeric'],
            'amount' => ['required', 'integer', 'min:0'],
        ]);

        $user = User::findOrFail($data['user_id']);
        // 같은 소속 아르바이트만
        $ok = $user->role === $me->role && $user->employment_type === 'part_time'
            && (int) $user->store_id === (int) $me->store_id
            && (int) $user->supplier_id === (int) $me->supplier_id;
        abort_unless($ok, 403);

        WageSettlement::updateOrCreate(
            ['user_id' => $user->id, 'period_from' => $data['from'], 'period_to' => $data['to']],
            [
                'total_hours' => $data['hours'],
                'total_amount' => $data['amount'],
                'status' => 'paid',
                'paid_at' => now(),
                'paid_by' => $me->id,
            ]
        );

        $this->notify->notifyUsers(collect([$user]), 'wage_paid',
            '💰 급여 입금 처리', number_format((int) $data['amount']).'원 급여가 입금 처리되었습니다.',
            ['from' => $data['from'], 'to' => $data['to']]);

        return back()->with('success', "{$user->name}님 급여 ".number_format((int) $data['amount']).'원을 입금 처리했습니다.');
    }

    /** 입금 처리 취소 */
    public function unpay(WageSettlement $settlement)
    {
        $me = Auth::user();
        abort_if($me->isPartTime(), 403);
        $user = $settlement->user;
        $ok = $user && $user->role === $me->role
            && (int) $user->store_id === (int) $me->store_id
            && (int) $user->supplier_id === (int) $me->supplier_id;
        abort_unless($ok, 403);

        $settlement->delete();

        return back()->with('success', '입금 처리를 취소했습니다.');
    }
}
