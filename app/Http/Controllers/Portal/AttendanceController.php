<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Leave;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 출퇴근 관리 — 아르바이트가 출근/퇴근 등록, 정직원이 승인.
 */
class AttendanceController extends Controller
{
    public function __construct(private NotificationService $notify)
    {
    }

    /** 아르바이트: 출퇴근 화면 (정직원은 승인 화면으로) */
    public function index()
    {
        $me = Auth::user();
        if (! $me->isPartTime()) {
            return redirect()->route('portal.attendance.approvals');
        }

        $open = Attendance::where('user_id', $me->id)->whereNull('clock_out_at')
            ->latest('clock_in_at')->first();
        $records = Attendance::where('user_id', $me->id)
            ->latest('clock_in_at')->limit(60)->get();

        return view('portal.attendance.index', compact('open', 'records'));
    }

    /** 출근 */
    public function clockIn()
    {
        $me = Auth::user();
        abort_unless($me->isPartTime(), 403);

        if (Attendance::where('user_id', $me->id)->whereNull('clock_out_at')->exists()) {
            return back()->with('error', '이미 출근 중입니다. 먼저 퇴근을 등록해 주세요.');
        }

        $att = Attendance::create([
            'user_id' => $me->id,
            'role' => $me->role,
            'store_id' => $me->store_id,
            'supplier_id' => $me->supplier_id,
            'work_date' => today(),
            'clock_in_at' => now(),
            'status' => 'pending',
        ]);

        $this->notify->notifyUsers($me->orgRegularStaff(), 'attendance',
            '🕐 출근 등록', "{$me->name}님이 출근했습니다 ({$att->clock_in_at->format('H:i')})",
            ['attendance_id' => $att->id]);

        return back()->with('success', '출근을 등록했습니다.');
    }

    /** 퇴근 */
    public function clockOut()
    {
        $me = Auth::user();
        abort_unless($me->isPartTime(), 403);

        $att = Attendance::where('user_id', $me->id)->whereNull('clock_out_at')
            ->latest('clock_in_at')->first();
        if (! $att) {
            return back()->with('error', '출근 기록이 없습니다.');
        }

        $att->update(['clock_out_at' => now()]);

        $this->notify->notifyUsers($me->orgRegularStaff(), 'attendance',
            '🕐 퇴근 등록', "{$me->name}님이 퇴근했습니다 ({$att->clock_out_at->format('H:i')} · {$att->hours()}시간)",
            ['attendance_id' => $att->id]);

        return back()->with('success', '퇴근을 등록했습니다.');
    }

    /** 정직원: 근태 승인 화면 (출퇴근 + 휴무 대기 목록) */
    public function approvals(Request $request)
    {
        $me = Auth::user();
        abort_if($me->isPartTime(), 403);

        $attendances = Attendance::forOrg($me)->with('user')
            ->orderByRaw("status = 'pending' desc")->latest('clock_in_at')->limit(100)->get();
        $leaves = Leave::forOrg($me)->with('user')
            ->orderByRaw("status = 'pending' desc")->latest('leave_date')->limit(100)->get();

        return view('portal.attendance.approvals', compact('attendances', 'leaves'));
    }

    public function approve(Attendance $attendance)
    {
        $this->authorizeOrg($attendance);
        $attendance->update(['status' => 'approved', 'approved_by' => Auth::id(), 'approved_at' => now()]);
        $this->notifyOwner($attendance->user_id, '✅ 출퇴근 승인', "{$attendance->work_date->format('m/d')} 출퇴근이 승인되었습니다.");

        return back()->with('success', '승인했습니다.');
    }

    public function reject(Attendance $attendance)
    {
        $this->authorizeOrg($attendance);
        $attendance->update(['status' => 'rejected', 'approved_by' => Auth::id(), 'approved_at' => now()]);
        $this->notifyOwner($attendance->user_id, '⛔ 출퇴근 반려', "{$attendance->work_date->format('m/d')} 출퇴근이 반려되었습니다.");

        return back()->with('success', '반려했습니다.');
    }

    /** 출퇴근 일괄 승인 — 퇴근 기록이 있는 대기 건만 */
    public function bulkApprove(Request $request)
    {
        $me = Auth::user();
        abort_if($me->isPartTime(), 403);

        $data = $request->validate([
            'attendance_ids' => ['required', 'array', 'min:1'],
            'attendance_ids.*' => ['integer'],
        ]);

        $items = Attendance::forOrg($me)
            ->whereIn('id', $data['attendance_ids'])
            ->where('status', 'pending')
            ->whereNotNull('clock_out_at')
            ->get();

        $ownerIds = [];
        foreach ($items as $att) {
            $att->update(['status' => 'approved', 'approved_by' => $me->id, 'approved_at' => now()]);
            $ownerIds[] = $att->user_id;
        }
        foreach (array_unique($ownerIds) as $uid) {
            $this->notifyOwner($uid, '✅ 출퇴근 승인', '출퇴근 기록이 승인되었습니다.');
        }

        return back()->with('success', count($items).'건을 일괄 승인했습니다.');
    }

    private function authorizeOrg(Attendance $attendance): void
    {
        $me = Auth::user();
        abort_if($me->isPartTime(), 403);
        $ok = $attendance->role === $me->role
            && (int) $attendance->store_id === (int) $me->store_id
            && (int) $attendance->supplier_id === (int) $me->supplier_id;
        abort_unless($ok, 403);
    }

    private function notifyOwner(int $userId, string $title, string $body): void
    {
        $owner = \App\Models\User::find($userId);
        if ($owner) {
            $this->notify->notifyUsers(collect([$owner]), 'attendance', $title, $body, []);
        }
    }
}
