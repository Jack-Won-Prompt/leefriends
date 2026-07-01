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

    /** 아르바이트 본인: 출퇴근 수동 등록 */
    public function store(Request $request)
    {
        $me = Auth::user();
        abort_unless($me->isPartTime(), 403);

        $data = $this->validateTimes($request);
        [$in, $out] = $this->buildTimes($data);

        Attendance::create([
            'user_id' => $me->id,
            'role' => $me->role,
            'store_id' => $me->store_id,
            'supplier_id' => $me->supplier_id,
            'work_date' => $data['work_date'],
            'clock_in_at' => $in,
            'clock_out_at' => $out,
            'status' => 'pending',
        ]);

        $this->notify->notifyUsers($me->orgRegularStaff(), 'attendance',
            '🕐 출퇴근 등록', "{$me->name}님이 {$in->format('m/d')} 출퇴근을 등록했습니다.", []);

        return back()->with('success', '출퇴근을 등록했습니다. 정직원 승인 후 확정됩니다.');
    }

    /** 아르바이트 본인: 출퇴근 수정 (수정 시 승인대기로 전환) */
    public function updateOwn(Request $request, Attendance $attendance)
    {
        $me = Auth::user();
        abort_unless($me->isPartTime() && $attendance->user_id === $me->id, 403);
        abort_if($attendance->status === 'approved', 400, '승인된 출퇴근은 수정할 수 없습니다.');

        $data = $this->validateTimes($request);
        [$in, $out] = $this->buildTimes($data);

        $attendance->update([
            'work_date' => $data['work_date'],
            'clock_in_at' => $in,
            'clock_out_at' => $out,
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        return back()->with('success', '출퇴근을 수정했습니다. 다시 승인 대기 상태가 됩니다.');
    }

    /** 아르바이트 본인: 승인되지 않은 출퇴근 삭제 */
    public function destroyOwn(Attendance $attendance)
    {
        $me = Auth::user();
        abort_unless($me->isPartTime() && $attendance->user_id === $me->id, 403);
        abort_if($attendance->status === 'approved', 400, '승인된 출퇴근은 삭제할 수 없습니다.');

        $attendance->delete();

        return back()->with('success', '출퇴근 기록을 삭제했습니다.');
    }

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
        $in = \Illuminate\Support\Carbon::parse($data['work_date'].' '.$data['clock_in']);
        $out = ! empty($data['clock_out']) ? \Illuminate\Support\Carbon::parse($data['work_date'].' '.$data['clock_out']) : null;

        return [$in, $out];
    }

    /** 정직원: 근태 승인 화면 (출퇴근 + 휴무 대기 목록) */
    public function approvals(Request $request)
    {
        $me = Auth::user();
        abort_if($me->isPartTime(), 403);

        $status = $request->query('status', 'all');
        $userId = $request->query('user') ?: null;
        $from = $request->query('from') ?: null;
        $to = $request->query('to') ?: null;

        $parttimers = \App\Models\User::where('role', $me->role)->where('employment_type', 'part_time')
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

        $attendances = $attQ->orderByRaw("status = 'pending' desc")->latest('clock_in_at')
            ->paginate(15, ['*'], 'ap')->withQueryString();
        $leaves = $leaveQ->orderByRaw("status = 'pending' desc")->latest('leave_date')
            ->paginate(15, ['*'], 'lp')->withQueryString();

        return view('portal.attendance.approvals', compact('attendances', 'leaves', 'parttimers', 'status', 'userId', 'from', 'to'));
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

    /** 정직원: 특정 아르바이트의 출퇴근 관리(입력/수정/승인) 화면 */
    public function manage(Request $request, \App\Models\User $user)
    {
        $me = Auth::user();
        $this->assertManageable($user);

        $from = $request->query('from') ?: now()->startOfMonth()->format('Y-m-d');
        $to = $request->query('to') ?: now()->format('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $records = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$from, $to])
            ->orderByDesc('work_date')->orderByDesc('clock_in_at')->get();

        return view('portal.attendance.manage', compact('user', 'records', 'from', 'to'));
    }

    /** 정직원이 아르바이트 출퇴근을 직접 등록(입력) — 등록 시 즉시 승인 */
    public function storeManual(Request $request, \App\Models\User $user)
    {
        $me = Auth::user();
        $this->assertManageable($user);

        $data = $request->validate([
            'work_date' => ['required', 'date'],
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i', 'after:clock_in'],
        ], [
            'clock_in.required' => '출근 시간을 입력해 주세요.',
            'clock_out.after' => '퇴근 시간은 출근 시간 이후여야 합니다.',
        ]);

        $in = \Illuminate\Support\Carbon::parse($data['work_date'].' '.$data['clock_in']);
        $out = ! empty($data['clock_out']) ? \Illuminate\Support\Carbon::parse($data['work_date'].' '.$data['clock_out']) : null;

        Attendance::create([
            'user_id' => $user->id,
            'role' => $user->role,
            'store_id' => $user->store_id,
            'supplier_id' => $user->supplier_id,
            'work_date' => $data['work_date'],
            'clock_in_at' => $in,
            'clock_out_at' => $out,
            'status' => $out ? 'approved' : 'pending',
            'approved_by' => $out ? $me->id : null,
            'approved_at' => $out ? now() : null,
            'note' => '정직원 직접 입력',
        ]);

        return back()->with('success', '출퇴근을 등록했습니다.');
    }

    /** 정직원이 출퇴근 시간 수정(+승인 가능) */
    public function updateTimes(Request $request, Attendance $attendance)
    {
        $this->authorizeOrg($attendance);

        $data = $request->validate([
            'work_date' => ['required', 'date'],
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i', 'after:clock_in'],
            'approve' => ['nullable', 'boolean'],
        ], ['clock_out.after' => '퇴근 시간은 출근 시간 이후여야 합니다.']);

        $in = \Illuminate\Support\Carbon::parse($data['work_date'].' '.$data['clock_in']);
        $out = ! empty($data['clock_out']) ? \Illuminate\Support\Carbon::parse($data['work_date'].' '.$data['clock_out']) : null;

        $payload = [
            'work_date' => $data['work_date'],
            'clock_in_at' => $in,
            'clock_out_at' => $out,
        ];
        if (! empty($data['approve']) && $out) {
            $payload['status'] = 'approved';
            $payload['approved_by'] = Auth::id();
            $payload['approved_at'] = now();
        }
        $attendance->update($payload);

        if (! empty($data['approve']) && $out) {
            $this->notifyOwner($attendance->user_id, '✅ 출퇴근 승인', "{$attendance->work_date->format('m/d')} 출퇴근이 승인되었습니다.");
        }

        return back()->with('success', '출퇴근 시간을 수정했습니다.');
    }

    /** 대상 아르바이트가 내 소속인지 검증 */
    private function assertManageable(\App\Models\User $user): void
    {
        $me = Auth::user();
        abort_if($me->isPartTime(), 403);
        $ok = $user->employment_type === 'part_time'
            && $user->role === $me->role
            && (int) $user->store_id === (int) $me->store_id
            && (int) $user->supplier_id === (int) $me->supplier_id;
        abort_unless($ok, 403);
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
