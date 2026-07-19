<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationReviewed;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * 본사 — 자가 회원가입 신청 승인 / 반려.
 */
class RegistrationController extends Controller
{
    public function index()
    {
        $pending = User::pendingApproval()
            ->with(['store', 'supplier'])
            ->orderBy('created_at')
            ->paginate(20);

        return view('portal.hq.registrations.index', compact('pending'));
    }

    public function approve(Request $request, User $user, NotificationService $notifier)
    {
        if (! $user->isPendingApproval()) {
            return back()->withErrors(['user' => '이미 처리된 신청입니다.']);
        }

        $user->update([
            'approval_status' => User::APPROVAL_APPROVED,
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'rejected_reason' => null,
        ]);

        $this->safeMail($user->email, new RegistrationReviewed($user, true, null));

        return back()->with('success', "{$user->name}({$user->email}) 님의 가입을 승인했습니다.");
    }

    public function reject(Request $request, User $user)
    {
        if (! $user->isPendingApproval()) {
            return back()->withErrors(['user' => '이미 처리된 신청입니다.']);
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update([
            'approval_status' => User::APPROVAL_REJECTED,
            'rejected_reason' => $data['reason'] ?? null,
            'approved_at' => null,
            'approved_by' => Auth::id(),
        ]);

        $this->safeMail($user->email, new RegistrationReviewed($user, false, $data['reason'] ?? null));

        return back()->with('success', "{$user->name}({$user->email}) 님의 가입을 반려했습니다.");
    }

    /** 메일 발송 실패가 승인/반려 처리를 막지 않도록 격리 */
    private function safeMail(string $email, RegistrationReviewed $mailable): void
    {
        try {
            Mail::to($email)->send($mailable);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
