<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Models\WageSettlement;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 모바일 앱 — 아르바이트 급여. 정직원이 기간별 일당·합계 확인 + 입금 처리.
 * 웹 Portal\WageController 와 동일 로직/권한.
 */
class WageController extends Controller
{
    public function __construct(private NotificationService $notify)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $me = $request->user();
        abort_if($me->isPartTime(), 403, '정직원 계정만 사용할 수 있습니다.');

        $from = $request->query('from') ?: now()->startOfMonth()->format('Y-m-d');
        $to = $request->query('to') ?: now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $parttimers = User::where('role', $me->role)->where('employment_type', 'part_time')
            ->when($me->role === 'store', fn ($q) => $q->where('store_id', $me->store_id))
            ->when($me->role === 'supplier', fn ($q) => $q->where('supplier_id', $me->supplier_id))
            ->orderBy('name')->get();

        $rows = [];
        $grand = 0;
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
                'user_id' => $u->id,
                'name' => $u->name,
                'hourly_wage' => (int) ($u->hourly_wage ?? 0),
                'days' => $days,
                'hours' => $hours,
                'amount' => $amount,
                'paid' => (bool) $settlement,
                'settlement_id' => $settlement?->id,
                'paid_at' => $settlement?->paid_at?->format('Y-m-d'),
            ];
            $grand += $amount;
        }

        return response()->json([
            'from' => $from, 'to' => $to,
            'grand_amount' => $grand,
            'rows' => $rows,
        ]);
    }

    public function pay(Request $request): JsonResponse
    {
        $me = $request->user();
        abort_if($me->isPartTime(), 403);
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'from' => ['required', 'date'],
            'to' => ['required', 'date'],
            'hours' => ['required', 'numeric'],
            'amount' => ['required', 'integer', 'min:0'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $ok = $user->role === $me->role && $user->employment_type === 'part_time'
            && (int) $user->store_id === (int) $me->store_id
            && (int) $user->supplier_id === (int) $me->supplier_id;
        abort_unless($ok, 403);

        WageSettlement::updateOrCreate(
            ['user_id' => $user->id, 'period_from' => $data['from'], 'period_to' => $data['to']],
            [
                'total_hours' => $data['hours'], 'total_amount' => $data['amount'],
                'status' => 'paid', 'paid_at' => now(), 'paid_by' => $me->id,
            ]
        );
        $this->notify->notifyUsers(collect([$user]), 'wage_paid',
            '💰 급여 입금 처리', number_format((int) $data['amount']).'원 급여가 입금 처리되었습니다.',
            ['from' => $data['from'], 'to' => $data['to']]);

        return response()->json(['message' => "{$user->name}님 급여 ".number_format((int) $data['amount']).'원을 입금 처리했습니다.']);
    }

    public function unpay(Request $request, WageSettlement $settlement): JsonResponse
    {
        $me = $request->user();
        abort_if($me->isPartTime(), 403);
        $user = $settlement->user;
        $ok = $user && $user->role === $me->role
            && (int) $user->store_id === (int) $me->store_id
            && (int) $user->supplier_id === (int) $me->supplier_id;
        abort_unless($ok, 403);
        $settlement->delete();

        return response()->json(['message' => '입금 처리를 취소했습니다.']);
    }
}
