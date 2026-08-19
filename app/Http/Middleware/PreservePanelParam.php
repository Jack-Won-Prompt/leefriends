<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * MDI 워크스페이스(iframe) 대응.
 * 화면 탭(iframe)은 ?panel=1 모드로 렌더된다. 그 안에서 폼을 제출하면 컨트롤러가
 * panel 없는 URL 로 리다이렉트해 iframe 이 전체 셸을 중첩 로드하는 문제가 있다.
 * panel 컨텍스트(요청 쿼리 또는 Referer 에 panel=1)에서 온 요청의 리다이렉트에는
 * panel=1 을 붙여 iframe 이 계속 panel 모드로 머물게 한다.
 */
class PreservePanelParam
{
    public function handle(Request $request, Closure $next)
    {
        $fromPanel = $request->boolean('panel')
            || str_contains((string) $request->headers->get('referer'), 'panel=1');

        $response = $next($request);

        if ($fromPanel && $response instanceof RedirectResponse) {
            $url = $response->getTargetUrl();
            if (str_contains($url, '/portal') && ! preg_match('/[?&]panel=1(?:&|$)/', $url)) {
                $response->setTargetUrl($url.(str_contains($url, '?') ? '&' : '?').'panel=1');
            }
        }

        return $response;
    }
}
