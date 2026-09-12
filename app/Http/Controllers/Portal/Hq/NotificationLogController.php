<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * FCM/인앱 알림 이력 — 본사·매장 수신자별 조회.
 * app_notifications(수신자별 저장) 를 users·stores 와 조인해 역할/매장으로 필터한다.
 */
class NotificationLogController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->query('role', 'all');          // all | hq | store
        $storeId = $request->query('store') ?: null;
        $type = $request->query('type') ?: null;
        $from = $request->query('from') ?: now()->subDays(7)->toDateString();
        $to = $request->query('to') ?: today()->toDateString();
        $q = trim((string) $request->query('q', ''));

        $query = AppNotification::query()
            ->join('users', 'users.id', '=', 'app_notifications.user_id')
            ->leftJoin('stores', 'stores.id', '=', 'users.store_id')
            ->whereIn('users.role', ['hq', 'store'])
            ->when(in_array($role, ['hq', 'store'], true), fn ($w) => $w->where('users.role', $role))
            ->when($storeId, fn ($w) => $w->where('users.store_id', $storeId))
            ->when($type, fn ($w) => $w->where('app_notifications.type', $type))
            ->when($from, fn ($w) => $w->whereDate('app_notifications.created_at', '>=', $from))
            ->when($to, fn ($w) => $w->whereDate('app_notifications.created_at', '<=', $to))
            ->when($q !== '', fn ($w) => $w->where(function ($s) use ($q) {
                $s->where('app_notifications.title', 'like', "%{$q}%")
                    ->orWhere('app_notifications.body', 'like', "%{$q}%");
            }));

        $logs = (clone $query)
            ->orderByDesc('app_notifications.created_at')
            ->select(
                'app_notifications.*',
                'users.name as user_name',
                'users.role as user_role',
                'stores.name as store_name',
            )
            ->paginate(30)
            ->withQueryString();

        // 요약: 본사/매장 수신 건수
        $hqCount = (clone $query)->where('users.role', 'hq')->count();
        $storeCount = (clone $query)->where('users.role', 'store')->count();

        $stores = Store::orderBy('name')->get(['id', 'name']);
        $types = AppNotification::select('type')->distinct()->orderBy('type')->pluck('type');

        return view('portal.hq.notification_logs.index', [
            'logs' => $logs,
            'stores' => $stores,
            'types' => $types,
            'role' => $role,
            'storeId' => $storeId,
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'q' => $q,
            'hqCount' => $hqCount,
            'storeCount' => $storeCount,
        ]);
    }
}
