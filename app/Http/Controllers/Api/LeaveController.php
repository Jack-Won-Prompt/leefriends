<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 모바일 앱 — 휴무. 아르바이트가 신청, 정직원이 승인.
 * 웹 Portal\LeaveController 와 동일 로직/권한.
 */
class LeaveController extends Controller
{
    public function __construct(private NotificationService $notify)
    {
    }

    /** 아르바이트 본인 휴무 목록 */
    public function index(Request $request): JsonResponse
    {
        $me = $request->user();
        $leaves = Leave::where('user_id', $me->id)->latest('leave_date')->limit(60)->get();

        return response()->json([
            'is_part_time' => $me->isPartTime(),
            'leaves' => $leaves->map(fn (Leave $l) => $this->row($l))->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $me = $request->user();
        abort_unless($me->isPartTime(), 403, '아르바이트 계정만 사용할 수 있습니다.');
        $data = $request->validate([
            'leave_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:200'],
        ], ['leave_date.required' => '휴무 날짜를 선택해 주세요.']);

        $leave = Leave::create([
            'user_id' => $me->id, 'role' => $me->role,
            'store_id' => $me->store_id, 'supplier_id' => $me->supplier_id,
            'leave_date' => $data['leave_date'], 'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);
        $this->notify->notifyUsers($me->orgRegularStaff(), 'leave',
            '🌴 휴무 신청', "{$me->name}님이 {$leave->leave_date->format('m/d')} 휴무를 신청했습니다.",
            ['leave_id' => $leave->id]);

        return response()->json(['message' => '휴무를 신청했습니다.', 'data' => $this->row($leave)], 201);
    }

    public function destroy(Request $request, Leave $leave): JsonResponse
    {
        abort_unless($leave->user_id === $request->user()->id, 403);
        if ($leave->status === 'approved') {
            return response()->json(['message' => '승인된 휴무는 취소할 수 없습니다.'], 422);
        }
        $leave->delete();

        return response()->json(['message' => '휴무 신청을 취소했습니다.']);
    }

    public function approve(Request $request, Leave $leave): JsonResponse
    {
        $this->authorizeOrg($request, $leave);
        $leave->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        $this->notifyOwner($leave->user_id, '✅ 휴무 승인', "{$leave->leave_date->format('m/d')} 휴무가 승인되었습니다.");

        return response()->json(['message' => '휴무를 승인했습니다.']);
    }

    public function reject(Request $request, Leave $leave): JsonResponse
    {
        $this->authorizeOrg($request, $leave);
        $leave->update(['status' => 'rejected', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        $this->notifyOwner($leave->user_id, '⛔ 휴무 반려', "{$leave->leave_date->format('m/d')} 휴무가 반려되었습니다.");

        return response()->json(['message' => '휴무를 반려했습니다.']);
    }

    private function authorizeOrg(Request $request, Leave $leave): void
    {
        $me = $request->user();
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

    private function row(Leave $l): array
    {
        return [
            'id' => $l->id,
            'leave_date' => $l->leave_date?->format('Y-m-d'),
            'reason' => $l->reason,
            'status' => $l->status,
            'status_label' => $l->statusLabel(),
        ];
    }
}
