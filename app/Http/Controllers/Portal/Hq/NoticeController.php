<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\PortalNotice;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 본사 → 매장/공급처 공지사항 작성·발송·관리.
 */
class NoticeController extends Controller
{
    public function index()
    {
        return view('portal.hq.notices.index', [
            'notices' => PortalNotice::with('author')->sorted()->paginate(15),
            'audiences' => PortalNotice::AUDIENCES,
        ]);
    }

    public function store(Request $request, NotificationService $notifications)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:5000'],
            'audience' => ['required', 'in:all,store,supplier'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $notice = PortalNotice::create([
            'title' => $data['title'],
            'content' => $data['content'],
            'audience' => $data['audience'],
            'is_pinned' => $request->boolean('is_pinned'),
            'created_by' => Auth::id(),
        ]);

        // 대상(매장/공급처) 사용자 전원에게 실시간 알림(인앱+토스트)
        $targets = User::whereIn('role', $notice->targetRoles())->get();
        $notifications->notifyUsers(
            $targets,
            'portal_notice',
            '📢 새 공지사항',
            $notice->title,
            ['portal_notice_id' => $notice->id],
        );

        return redirect()->route('portal.hq.notices.index')
            ->with('success', "공지사항을 발송했습니다. (대상: {$notice->audience_label}, 수신 {$targets->count()}명)");
    }

    public function destroy(PortalNotice $notice)
    {
        $notice->delete();

        return redirect()->route('portal.hq.notices.index')->with('success', '공지사항을 삭제했습니다.');
    }
}
