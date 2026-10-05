<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\PortalNotice;
use App\Models\Store;
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
            'notices' => PortalNotice::with(['author', 'store'])->sorted()->paginate(15),
            'audiences' => PortalNotice::AUDIENCES,
            'stores' => Store::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, NotificationService $notifications)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:5000'],
            'audience' => ['required', 'in:all,store,supplier'],
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        // 단일 매장 타겟은 audience=store 일 때만 유효
        $storeId = $data['audience'] === 'store' ? ($data['store_id'] ?? null) : null;

        $notice = PortalNotice::create([
            'title' => $data['title'],
            'content' => $data['content'],
            'audience' => $data['audience'],
            'store_id' => $storeId,
            'is_pinned' => $request->boolean('is_pinned'),
            'created_by' => Auth::id(),
        ]);

        // 대상 사용자 전원에게 실시간 알림(인앱+토스트+FCM). 단일 매장이면 그 매장만.
        // 앱은 알림 본문을 그대로 보여주므로 본문에 공지 내용을 담는다.
        $targets = $notice->targetUsers()->get();
        $notifications->notifyUsers(
            $targets,
            'portal_notice',
            '📢 '.$notice->title,
            $notice->content,
            ['portal_notice_id' => $notice->id],
        );

        return redirect()->route('portal.hq.notices.index')
            ->with('success', "공지사항을 발송했습니다. (대상: {$notice->target_label}, 수신 {$targets->count()}명)");
    }

    public function destroy(PortalNotice $notice)
    {
        $notice->delete();

        return redirect()->route('portal.hq.notices.index')->with('success', '공지사항을 삭제했습니다.');
    }
}
