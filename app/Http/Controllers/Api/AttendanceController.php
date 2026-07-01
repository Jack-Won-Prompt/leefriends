<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * 모바일 앱 — 출퇴근 관리. 아르바이트(part_time)가 등록, 정직원(regular)이 승인.
 * 웹 Portal\AttendanceController 와 동일 로직/권한.
 */
class AttendanceController extends Controller
{
    public function __construct(private NotificationService $notify)
    {
    }

    /** 아르바이트 본인: 진행중 + 최근 기록 */
    public function index(Request $request): JsonResponse
    {
        $me = $request->user();
        $open = Attendance::where('user_id', $me->id)->whereNull('clock_out_at')
            ->latest('clock_in_at')->first();
        $records = Attendance::where('user_id', $me->id)
            ->latest('clock_in_at')->limit(60)->get();

        return response()->json([
            'is_part_time' => $me->isPartTime(),
            'open' => $open ? $this->row($open) : null,
            'records' => $records->map(fn (Attendance $a) => $this->row($a))->all(),
        ]);
    }

    public function clockIn(Request $request): JsonResponse
    {
        $me = $request->user();
        abort_unless($me->isPartTime(), 403, '아르바이트 계정만 사용할 수 있습니다.');

        if (Attendance::where('user_id', $me->id)->whereNull('clock_out_at')->exists()) {
            return response()->json(['message' => '이미 출근 중입니다. 먼저 퇴근을 등록해 주세요.'], 422);
        }

        $att = Attendance::create([
            'user_id' => $me->id, 'role' => $me->role,
            'store_id' => $me->store_id, 'supplier_id' => $me->supplier_id,
            'work_date' => today(), 'clock_in_at' => now(), 'status' => 'pending',
        ]);
        $this->notify->notifyUsers($me->orgRegularStaff(), 'attendance',
            '🕐 출근 등록', "{$me->name}님이 출근했습니다 ({$att->clock_in_at->format('H:i')})",
            ['attendance_id' => $att->id]);

        return response()->json(['message' => '출근을 등록했습니다.', 'data' => $this->row($att)], 201);
    }

    public function clockOut(Request $request): JsonResponse
    {
        $me = $request->user();
        abort_unless($me->isPartTime(), 403, '아르바이트 계정만 사용할 수 있습니다.');

        $att = Attendance::where('user_id', $me->id)->whereNull('clock_out_at')
            ->latest('clock_in_at')->first();
        if (! $att) {
            return response()->json(['message' => '출근 기록이 없습니다.'], 422);
        }
        $att->update(['clock_out_at' => now()]);
        $this->notify->notifyUsers($me->orgRegularStaff(), 'attendance',
            '🕐 퇴근 등록', "{$me->name}님이 퇴근했습니다 ({$att->clock_out_at->format('H:i')} · {$att->hours()}시간)",
            ['attendance_id' => $att->id]);

        return response()->json(['message' => '퇴근을 등록했습니다.', 'data' => $this->row($att)]);
    }

    /** 아르바이트 본인: 수동 등록 */
    public function store(Request $request): JsonResponse
    {
        $me = $request->user();
        abort_unless($me->isPartTime(), 403, '아르바이트 계정만 사용할 수 있습니다.');
        $data = $this->validateTimes($request);
        [$in, $out] = $this->buildTimes($data);

        $att = Attendance::create([
            'user_id' => $me->id, 'role' => $me->role,
            'store_id' => $me->store_id, 'supplier_id' => $me->supplier_id,
            'work_date' => $data['work_date'], 'clock_in_at' => $in, 'clock_out_at' => $out,
            'status' => 'pending',
        ]);
        $this->notify->notifyUsers($me->orgRegularStaff(), 'attendance',
            '🕐 출퇴근 등록', "{$me->name}님이 {$in->format('m/d')} 출퇴근을 등록했습니다.", []);

        return response()->json(['message' => '출퇴근을 등록했습니다. 정직원 승인 후 확정됩니다.', 'data' => $this->row($att)], 201);
    }

    public function updateOwn(Request $request, Attendance $attendance): JsonResponse
    {
        $me = $request->user();
        abort_unless($me->isPartTime() && $attendance->user_id === $me->id, 403);
        if ($attendance->status === 'approved') {
            return response()->json(['message' => '승인된 출퇴근은 수정할 수 없습니다.'], 422);
        }
        $data = $this->validateTimes($request);
        [$in, $out] = $this->buildTimes($data);
        $attendance->update([
            'work_date' => $data['work_date'], 'clock_in_at' => $in, 'clock_out_at' => $out,
            'status' => 'pending', 'approved_by' => null, 'approved_at' => null,
        ]);

        return response()->json(['message' => '출퇴근을 수정했습니다. 다시 승인 대기 상태가 됩니다.', 'data' => $this->row($attendance)]);
    }

    public function destroyOwn(Request $request, Attendance $attendance): JsonResponse
    {
        $me = $request->user();
        abort_unless($me->isPartTime() && $attendance->user_id === $me->id, 403);
        if ($attendance->status === 'approved') {
            return response()->json(['message' => '승인된 출퇴근은 삭제할 수 없습니다.'], 422);
        }
        $attendance->delete();

        return response()->json(['message' => '출퇴근 기록을 삭제했습니다.']);
    }

    /** 정직원: 승인 목록 (출퇴근 + 휴무 대기) + 아르바이트 목록 */
    public function approvals(Request $request): JsonResponse
    {
        $me = $request->user();
        abort_if($me->isPartTime(), 403, '정직원 계정만 사용할 수 있습니다.');

        $status = $request->query('status', 'all');
        $userId = $request->query('user') ?: null;
        $from = $request->query('from') ?: null;
        $to = $request->query('to') ?: null;

        $parttimers = User::where('role', $me->role)->where('employment_type', 'part_time')
            ->when($me->role === 'store', fn ($q) => $q->where('store_id', $me->store_id))
            ->when($me->role === 'supplier', fn ($q) => $q->where('supplier_id', $me->supplier_id))
            ->orderBy('name')->get(['id', 'name']);

        $attQ = Attendance::forOrg($me)->with('user');
        $leaveQ = Leave::forOrg($me)->with('user');
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $attQ->where('status', $status);
            $leaveQ->where('status', $status);
        }
        if ($userId) {
            $attQ->where('user_id', $userId);
            $leaveQ->where('user_id', $userId);
        }
        if ($from) {
            $attQ->whereDate('work_date', '>=', $from);
            $leaveQ->whereDate('leave_date', '>=', $from);
        }
        if ($to) {
            $attQ->whereDate('work_date', '<=', $to);
            $leaveQ->whereDate('leave_date', '<=', $to);
        }

        $attendances = $attQ->orderByRaw("status = 'pending' desc")->latest('clock_in_at')->limit(100)->get();
        $leaves = $leaveQ->orderByRaw("status = 'pending' desc")->latest('leave_date')->limit(100)->get();

        return response()->json([
            'parttimers' => $parttimers->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->all(),
            'attendances' => $attendances->map(fn (Attendance $a) => $this->row($a, true))->all(),
            'leaves' => $leaves->map(fn (Leave $l) => $this->leaveRow($l, true))->all(),
        ]);
    }

    public function approve(Request $request, Attendance $attendance): JsonResponse
    {
        $this->authorizeOrg($request, $attendance);
        $attendance->update(['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        $this->notifyOwner($attendance->user_id, '✅ 출퇴근 승인', "{$attendance->work_date->format('m/d')} 출퇴근이 승인되었습니다.");

        return response()->json(['message' => '승인했습니다.']);
    }

    public function reject(Request $request, Attendance $attendance): JsonResponse
    {
        $this->authorizeOrg($request, $attendance);
        $attendance->update(['status' => 'rejected', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
        $this->notifyOwner($attendance->user_id, '⛔ 출퇴근 반려', "{$attendance->work_date->format('m/d')} 출퇴근이 반려되었습니다.");

        return response()->json(['message' => '반려했습니다.']);
    }

    public function bulkApprove(Request $request): JsonResponse
    {
        $me = $request->user();
        abort_if($me->isPartTime(), 403);
        $data = $request->validate([
            'attendance_ids' => ['required', 'array', 'min:1'],
            'attendance_ids.*' => ['integer'],
        ]);
        $items = Attendance::forOrg($me)->whereIn('id', $data['attendance_ids'])
            ->where('status', 'pending')->whereNotNull('clock_out_at')->get();
        $ownerIds = [];
        foreach ($items as $att) {
            $att->update(['status' => 'approved', 'approved_by' => $me->id, 'approved_at' => now()]);
            $ownerIds[] = $att->user_id;
        }
        foreach (array_unique($ownerIds) as $uid) {
            $this->notifyOwner($uid, '✅ 출퇴근 승인', '출퇴근 기록이 승인되었습니다.');
        }

        return response()->json(['message' => count($items).'건을 일괄 승인했습니다.', 'count' => count($items)]);
    }

    /** 정직원: 특정 아르바이트 출퇴근 관리 조회 */
    public function manage(Request $request, User $user): JsonResponse
    {
        $this->assertManageable($request, $user);
        $from = $request->query('from') ?: now()->startOfMonth()->format('Y-m-d');
        $to = $request->query('to') ?: now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $records = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$from, $to])
            ->orderByDesc('work_date')->orderByDesc('clock_in_at')->get();

        return response()->json([
            'user' => ['id' => $user->id, 'name' => $user->name],
            'from' => $from, 'to' => $to,
            'records' => $records->map(fn (Attendance $a) => $this->row($a))->all(),
        ]);
    }

    /** 정직원이 아르바이트 출퇴근 직접 등록 (퇴근 있으면 즉시 승인) */
    public function storeManual(Request $request, User $user): JsonResponse
    {
        $me = $request->user();
        $this->assertManageable($request, $user);
        $data = $this->validateTimes($request);
        [$in, $out] = $this->buildTimes($data);

        $att = Attendance::create([
            'user_id' => $user->id, 'role' => $user->role,
            'store_id' => $user->store_id, 'supplier_id' => $user->supplier_id,
            'work_date' => $data['work_date'], 'clock_in_at' => $in, 'clock_out_at' => $out,
            'status' => $out ? 'approved' : 'pending',
            'approved_by' => $out ? $me->id : null,
            'approved_at' => $out ? now() : null,
            'note' => '정직원 직접 입력',
        ]);

        return response()->json(['message' => '출퇴근을 등록했습니다.', 'data' => $this->row($att)], 201);
    }

    /** 정직원이 출퇴근 시간 수정(+승인) */
    public function updateTimes(Request $request, Attendance $attendance): JsonResponse
    {
        $this->authorizeOrg($request, $attendance);
        $data = $request->validate([
            'work_date' => ['required', 'date'],
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i', 'after:clock_in'],
            'approve' => ['nullable', 'boolean'],
        ], ['clock_out.after' => '퇴근 시간은 출근 시간 이후여야 합니다.']);
        $in = Carbon::parse($data['work_date'].' '.$data['clock_in']);
        $out = ! empty($data['clock_out']) ? Carbon::parse($data['work_date'].' '.$data['clock_out']) : null;

        $payload = ['work_date' => $data['work_date'], 'clock_in_at' => $in, 'clock_out_at' => $out];
        if (! empty($data['approve']) && $out) {
            $payload += ['status' => 'approved', 'approved_by' => $request->user()->id, 'approved_at' => now()];
        }
        $attendance->update($payload);
        if (! empty($data['approve']) && $out) {
            $this->notifyOwner($attendance->user_id, '✅ 출퇴근 승인', "{$attendance->work_date->format('m/d')} 출퇴근이 승인되었습니다.");
        }

        return response()->json(['message' => '출퇴근 시간을 수정했습니다.', 'data' => $this->row($attendance->fresh())]);
    }

    // ---- helpers ----
    private function validateTimes(Request $request): array
    {
        return $request->validate([
            'work_date' => ['required', 'date'],
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i', 'after:clock_in'],
        ], [
            'clock_in.required' => '출근 시간을 입력해 주세요.',
            'clock_out.after' => '퇴근 시간은 출근 시간 이후여야 합니다.',
        ]);
    }

    private function buildTimes(array $data): array
    {
        $in = Carbon::parse($data['work_date'].' '.$data['clock_in']);
        $out = ! empty($data['clock_out']) ? Carbon::parse($data['work_date'].' '.$data['clock_out']) : null;

        return [$in, $out];
    }

    private function assertManageable(Request $request, User $user): void
    {
        $me = $request->user();
        abort_if($me->isPartTime(), 403);
        $ok = $user->employment_type === 'part_time'
            && $user->role === $me->role
            && (int) $user->store_id === (int) $me->store_id
            && (int) $user->supplier_id === (int) $me->supplier_id;
        abort_unless($ok, 403);
    }

    private function authorizeOrg(Request $request, Attendance $attendance): void
    {
        $me = $request->user();
        abort_if($me->isPartTime(), 403);
        $ok = $attendance->role === $me->role
            && (int) $attendance->store_id === (int) $me->store_id
            && (int) $attendance->supplier_id === (int) $me->supplier_id;
        abort_unless($ok, 403);
    }

    private function notifyOwner(int $userId, string $title, string $body): void
    {
        $owner = User::find($userId);
        if ($owner) {
            $this->notify->notifyUsers(collect([$owner]), 'attendance', $title, $body, []);
        }
    }

    private function row(Attendance $a, bool $withUser = false): array
    {
        return [
            'id' => $a->id,
            'work_date' => $a->work_date?->format('Y-m-d'),
            'clock_in' => $a->clock_in_at?->format('H:i'),
            'clock_out' => $a->clock_out_at?->format('H:i'),
            'clock_in_at' => $a->clock_in_at?->format('Y-m-d H:i'),
            'clock_out_at' => $a->clock_out_at?->format('Y-m-d H:i'),
            'hours' => $a->hours(),
            'status' => $a->status,
            'status_label' => $a->statusLabel(),
            'is_open' => $a->isOpen(),
            'note' => $a->note,
            'user' => $withUser ? ['id' => $a->user?->id, 'name' => $a->user?->name] : null,
        ];
    }

    private function leaveRow(Leave $l, bool $withUser = false): array
    {
        return [
            'id' => $l->id,
            'leave_date' => $l->leave_date?->format('Y-m-d'),
            'reason' => $l->reason,
            'status' => $l->status,
            'status_label' => $l->statusLabel(),
            'user' => $withUser ? ['id' => $l->user?->id, 'name' => $l->user?->name] : null,
        ];
    }
}
