<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\PortalNotice;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 포털 공지사항 관리 — 본사 전용 (목록/발송/삭제).
 * 발송 시 대상(매장/공급처) 전원에게 인앱+FCM 알림.
 */
class NoticeController extends Controller
{
    use ResolvesSeller;

    private function ensureHq(Request $request): void
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureHq($request);
        $notices = PortalNotice::with('author')->sorted()->paginate(30);

        return response()->json([
            'data' => $notices->getCollection()->map(fn (PortalNotice $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'content' => $n->content,
                'audience' => $n->audience,
                'audience_label' => PortalNotice::AUDIENCES[$n->audience] ?? $n->audience,
                'is_pinned' => (bool) $n->is_pinned,
                'author' => $n->author?->name,
                'created_at' => $n->created_at?->format('Y-m-d H:i'),
            ])->values(),
            'meta' => [
                'audiences' => collect(PortalNotice::AUDIENCES)
                    ->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
            ],
        ]);
    }

    public function store(Request $request, NotificationService $notifications): JsonResponse
    {
        $this->ensureHq($request);
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
            'created_by' => $request->user()->id,
        ]);

        $targets = User::whereIn('role', $notice->targetRoles())->get();
        $notifications->notifyUsers($targets, 'portal_notice', '📢 새 공지사항', $notice->title,
            ['portal_notice_id' => $notice->id]);

        return response()->json([
            'message' => "공지를 발송했습니다. (대상: {$notice->audience_label}, 수신 {$targets->count()}명)",
        ], 201);
    }

    public function destroy(Request $request, PortalNotice $notice): JsonResponse
    {
        $this->ensureHq($request);
        $notice->delete();

        return response()->json(['message' => '공지를 삭제했습니다.']);
    }
}
