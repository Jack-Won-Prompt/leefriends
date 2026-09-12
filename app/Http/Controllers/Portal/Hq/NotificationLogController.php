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
 * 조회 조건·필터는 AppNotification 모델에 두어 앱 API(seller/notification-logs)와 공유.
 */
class NotificationLogController extends Controller
{
    public function index(Request $request): View
    {
        $f = AppNotification::logFilters($request->query());
        $query = AppNotification::query()->logFilter($f);

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
            'role' => $f['role'],
            'storeId' => $f['store'],
            'type' => $f['type'],
            'from' => $f['from'],
            'to' => $f['to'],
            'q' => $f['q'],
            'hqCount' => $hqCount,
            'storeCount' => $storeCount,
        ]);
    }
}
