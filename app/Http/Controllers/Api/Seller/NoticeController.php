<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\PortalNotice;
use App\Models\Store;
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
        $notices = PortalNotice::with(['author', 'store'])->sorted()->paginate(30);

        return response()->json([
            'data' => $notices->getCollection()->map(fn (PortalNotice $n) => [
                'id' => $n->id,
                'title' => $n->title,
                'content' => $n->content,
                'audience' => $n->audience,
                'audience_label' => PortalNotice::AUDIENCES[$n->audience] ?? $n->audience,
                'store_id' => $n->store_id,
                'target_label' => $n->target_label,
                'is_pinned' => (bool) $n->is_pinned,
                'author' => $n->author?->name,
                'created_at' => $n->created_at?->format('Y-m-d H:i'),
            ])->values(),
            'meta' => [
                'audiences' => collect(PortalNotice::AUDIENCES)
                    ->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
                'stores' => Store::active()->orderBy('name')->get(['id', 'name'])
                    ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
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
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'is_pinned' => ['nullable', 'boolean'],
        ]);

        $storeId = $data['audience'] === 'store' ? ($data['store_id'] ?? null) : null;

        $notice = PortalNotice::create([
            'title' => $data['title'],
            'content' => $data['content'],
            'audience' => $data['audience'],
            'store_id' => $storeId,
            'is_pinned' => $request->boolean('is_pinned'),
            'created_by' => $request->user()->id,
        ]);

        $targets = $notice->targetUsers()->get();
        // 앱은 알림 본문을 그대로 보여주므로 본문에 공지 내용을 담는다.
        $notifications->notifyUsers($targets, 'portal_notice', '📢 '.$notice->title, $notice->content,
            ['portal_notice_id' => $notice->id]);

        return response()->json([
            'message' => "공지를 발송했습니다. (대상: {$notice->target_label}, 수신 {$targets->count()}명)",
        ], 201);
    }

    public function destroy(Request $request, PortalNotice $notice): JsonResponse
    {
        $this->ensureHq($request);
        $notice->delete();

        return response()->json(['message' => '공지를 삭제했습니다.']);
    }
}
