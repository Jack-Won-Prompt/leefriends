<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 아르바이트 직원은 근태관리(출퇴근·휴무)와 로그아웃만 접근 가능.
 * 그 외 포털 화면 요청은 출퇴근 화면으로 리다이렉트한다.
 */
class RestrictPartTime
{
    /** 아르바이트 허용 라우트 이름 프리픽스 */
    private array $allowed = [
        'portal.attendance.',
        'portal.leaves.',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user || ! $user->isPartTime()) {
            return $next($request);
        }

        $name = (string) optional($request->route())->getName();

        // 로그아웃·로그인은 허용
        if (in_array($name, ['portal.logout', 'portal.login'], true)) {
            return $next($request);
        }
        foreach ($this->allowed as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return $next($request);
            }
        }

        return redirect()->route('portal.attendance.index');
    }
}
