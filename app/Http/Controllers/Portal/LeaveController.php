<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 휴무 관리 — 아르바이트가 신청, 정직원이 승인.
 */
class LeaveController extends Controller
{
    public function __construct(private NotificationService $notify)
    {
    }

    /** 아르바이트: 휴무 신청 화면 */
    public function index()
    {
        $me = Auth::user();
        if (! $me->isPartTime()) {
            return redirect()->route('portal.attendance.approvals');
        }

        $leaves = Leave::where('user_id', $me->id)->latest('leave_date')->limit(60)->get();

        return view('portal.leaves.index', compact('leaves'));
    }

    public function store(Request $request)
    {
        $me = Auth::user();
        abort_unless($me->isPartTime(), 403);

        $data = $request->validate([
            'leave_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:200'],
        ], ['leave_date.required' => '휴무 날짜를 선택해 주세요.']);

        $leave = Leave::create([
            'user_id' => $me->id,
            'role' => $me->role,
            'store_id' => $me->store_id,
            'supplier_id' => $me->supplier_id,
            'leave_date' => $data['leave_date'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);

        $this->notify->notifyUsers($me->orgRegularStaff(), 'leave',
            '🌴 휴무 신청', "{$me->name}님이 {$leave->leave_date->format('m/d')} 휴무를 신청했습니다.",
            ['leave_id' => $leave->id]);

        return back()->with('success', '휴무를 신청했습니다.');
    }

    public function destroy(Leave $leave)
    {
        abort_unless($leave->user_id === Auth::id(), 403);
        abort_if($leave->status === 'approved', 400, '승인된 휴무는 취소할 수 없습니다.');
        $leave->delete();

        return back()->with('success', '휴무 신청을 취소했습니다.');
    }

    public function approve(Leave $leave)
    {
        $this->authorizeOrg($leave);
        $leave->update(['status' => 'approved', 'approved_by' => Auth::id(), 'approved_at' => now()]);
        $this->notifyOwner($leave->user_id, '✅ 휴무 승인', "{$leave->leave_date->format('m/d')} 휴무가 승인되었습니다.");

        return back()->with('success', '휴무를 승인했습니다.');
    }

    public function reject(Leave $leave)
    {
        $this->authorizeOrg($leave);
        $leave->update(['status' => 'rejected', 'approved_by' => Auth::id(), 'approved_at' => now()]);
        $this->notifyOwner($leave->user_id, '⛔ 휴무 반려', "{$leave->leave_date->format('m/d')} 휴무가 반려되었습니다.");

        return back()->with('success', '휴무를 반려했습니다.');
    }

    private function authorizeOrg(Leave $leave): void
    {
        $me = Auth::user();
        abort_if($me->isPartTime(), 403);
        $ok = $leave->role === $me->role
            && (int) $leave->store_id === (int) $me->store_id
            && (int) $leave->supplier_id === (int) $me->supplier_id;
        abort_unless($ok, 403);
    }

    private function notifyOwner(int $userId, string $title, string $body): void
    {
        $owner = User::find($userId);
        if ($owner) {
            $this->notify->notifyUsers(collect([$owner]), 'leave', $title, $body, []);
        }
    }
}
