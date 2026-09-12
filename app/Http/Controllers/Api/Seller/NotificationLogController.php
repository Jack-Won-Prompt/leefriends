<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * FCM/인앱 알림 이력 (본사 전용) — 웹 포털 본사 «FCM 알림 이력» 과 같은 조건·집계.
 */
class NotificationLogController extends Controller
{
    use ResolvesSeller;

    /**
     * GET /api/v1/seller/notification-logs?role=all|hq|store&store=&type=&from=&to=&q=&page=
     */
    public function index(Request $request): JsonResponse
    {
        [$sellerType] = $this->seller($request);
        abort_unless($sellerType === 'hq', 403, '본사 계정만 사용할 수 있습니다.');

        $f = AppNotification::logFilters($request->query());
        $query = AppNotification::query()->logFilter($f);

        $rows = (clone $query)
            ->orderByDesc('app_notifications.created_at')
            ->orderByDesc('app_notifications.id')
            ->select(
                'app_notifications.*',
                'users.name as user_name',
                'users.role as user_role',
                'stores.name as store_name',
            )
            ->paginate(30);

        return response()->json([
            'data' => $rows->getCollection()->map(fn (AppNotification $n) => [
                'id' => $n->id,
                'type' => $n->type,
                'type_label' => AppNotification::typeLabel($n->type),
                'title' => $n->title,
                'body' => $n->body,
                'is_read' => $n->read_at !== null,
                'created_at' => $n->created_at?->format('Y.m.d H:i'),
                'user_name' => $n->user_name,
                'user_role' => $n->user_role,
                'store_name' => $n->store_name,
            ])->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'total' => $rows->total(),
                'hq_count' => (clone $query)->where('users.role', 'hq')->count(),
                'store_count' => (clone $query)->where('users.role', 'store')->count(),
                'filters' => $f,
                'stores' => Store::orderBy('name')->get(['id', 'name']),
                'types' => AppNotification::select('type')->distinct()->orderBy('type')->pluck('type')
                    ->map(fn ($t) => ['key' => $t, 'label' => AppNotification::typeLabel($t)])
                    ->values(),
            ],
        ]);
    }
}
