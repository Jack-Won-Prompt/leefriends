<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\OrderChange;
use Illuminate\Support\Facades\Auth;

class OrderChangeController extends Controller
{
    /** 현재 사용자(본사/공급처)의 판매자 컨텍스트 */
    public static function sellerContext($user): array
    {
        $role = $user->role ?: ($user->is_admin ? 'hq' : '');

        return $role === 'supplier' ? ['supplier', $user->supplier_id] : ['hq', null];
    }

    public function index()
    {
        [$type, $sid] = self::sellerContext(Auth::user());

        // 미반영(확인 전) 건만 표시 — 반영 완료 건은 목록에서 숨김(작업목록)
        $changes = OrderChange::forSeller($type, $sid)
            ->pending()
            ->with('store')
            ->latest()
            ->paginate(20);

        return view('portal.order_changes.index', compact('changes'));
    }

    public function ack(OrderChange $change)
    {
        [$type, $sid] = self::sellerContext(Auth::user());
        abort_unless($change->seller_type === $type && $change->supplier_id == $sid, 403);

        if (! $change->acknowledged_at) {
            $change->update(['acknowledged_at' => now(), 'acknowledged_by' => Auth::id()]);
        }

        return back()->with('success', '변경을 확인(반영)했습니다.');
    }

    public function ackAll()
    {
        [$type, $sid] = self::sellerContext(Auth::user());

        OrderChange::forSeller($type, $sid)->pending()
            ->update(['acknowledged_at' => now(), 'acknowledged_by' => Auth::id()]);

        return back()->with('success', '모든 변경을 확인(반영)했습니다.');
    }
}
