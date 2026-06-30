<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/v1/notifications
     * 내 알림 목록(최신순) + 안읽음 개수.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $notifications = AppNotification::where('user_id', $userId)
            // unread=1 이면 읽지 않은 알림만 (읽으면 목록에서 사라짐)
            ->when($request->boolean('unread'), fn ($q) => $q->unread())
            ->latest()
            ->paginate(20);

        $unread = AppNotification::where('user_id', $userId)->unread()->count();

        return response()->json([
            'data' => $notifications->getCollection()->map(fn (AppNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'data' => $n->data,
                'is_read' => $n->read_at !== null,
                'created_at' => $n->created_at?->format('Y-m-d H:i'),
            ])->values(),
            'meta' => [
                'unread' => $unread,
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread' => AppNotification::where('user_id', $request->user()->id)->unread()->count(),
        ]);
    }

    /**
     * POST /api/v1/notifications/{notification}/read
     */
    public function read(Request $request, AppNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['message' => '읽음 처리되었습니다.']);
    }

    /**
     * POST /api/v1/notifications/read-all
     */
    public function readAll(Request $request): JsonResponse
    {
        AppNotification::where('user_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['message' => '모든 알림을 읽음 처리했습니다.']);
    }
}
