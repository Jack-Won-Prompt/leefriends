<?php

namespace App\Http\Controllers\Api\Seller;

use Illuminate\Http\Request;

/**
 * 인증 사용자(본사/공급처)로부터 판매자 식별값을 해석.
 */
trait ResolvesSeller
{
    /** @return array{0:string,1:?int} [seller_type, supplier_id] */
    protected function seller(Request $request): array
    {
        $user = $request->user();
        $role = $user->role ?: ($user->is_admin ? 'hq' : null);

        if ($role === 'hq') {
            return ['hq', null];
        }
        if ($role === 'supplier') {
            abort_unless($user->supplier_id, 403, '연결된 공급처가 없는 계정입니다.');

            return ['supplier', $user->supplier_id];
        }

        abort(403, '본사 또는 공급처 계정만 사용할 수 있습니다.');
    }
}
