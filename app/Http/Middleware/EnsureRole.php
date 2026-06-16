<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * 포털 역할 가드. 예) ->middleware('role:store') / 'role:hq,supplier'
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('portal.login');
        }

        // 본사(hq)는 마케팅 관리자 계정(is_admin)도 허용
        $userRole = $user->role ?: ($user->is_admin ? 'hq' : '');

        if ($roles && ! in_array($userRole, $roles, true)) {
            abort(403, '접근 권한이 없습니다.');
        }

        return $next($request);
    }
}
