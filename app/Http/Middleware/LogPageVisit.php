<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 공개 사이트 페이지 방문을 익명 로깅한다 (본사 방문 분석용).
 * 응답 이후 defer 로 기록하여 요청 지연이 없다.
 */
class LogPageVisit
{
    /** 로깅 제외 경로 접두어 */
    private const SKIP_PREFIXES = ['portal', 'admin', 'api', 'up', 'telescope', '_debugbar', 'livewire', 'storage'];

    private const PAGE_NAMES = [
        'home' => '홈',
        'menu' => '메뉴',
        'franchise' => '창업 안내',
        'franchise.thanks' => '창업 문의 완료',
        'store' => '매장 찾기',
        'brand' => '브랜드',
        'notice.index' => '공지사항',
        'notice.show' => '공지 상세',
        'privacy' => '개인정보처리방침',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldLog($request, $response)) {
            $data = $this->extract($request);
            defer(function () use ($data) {
                try {
                    PageVisit::create($data);
                } catch (\Throwable) {
                    // 로깅 실패는 무시 (사용자 응답에 영향 없음)
                }
            });
        }

        return $response;
    }

    private function shouldLog(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return false;
        }
        if ($response->getStatusCode() !== 200) {
            return false;
        }
        $ctype = (string) $response->headers->get('Content-Type');
        if ($ctype && ! str_contains($ctype, 'text/html')) {
            return false;
        }
        $path = ltrim($request->path(), '/');
        foreach (self::SKIP_PREFIXES as $p) {
            if ($path === $p || str_starts_with($path, $p.'/')) {
                return false;
            }
        }
        // 봇 제외
        if (preg_match('/bot|crawler|spider|slurp|bingpreview|facebookexternalhit/i', (string) $request->userAgent())) {
            return false;
        }

        return true;
    }

    private function extract(Request $request): array
    {
        $ref = $request->headers->get('referer');
        $refHost = $ref ? parse_url($ref, PHP_URL_HOST) : null;
        $ua = (string) $request->userAgent();
        $sid = $request->hasSession() ? $request->session()->getId() : ($request->ip().'|'.$ua);

        return [
            'path' => '/'.ltrim($request->path(), '/'),
            'page_name' => self::PAGE_NAMES[$request->route()?->getName()] ?? null,
            'source' => $this->source($refHost, $request),
            'referrer' => $refHost ? mb_substr($refHost, 0, 255) : null,
            'device' => preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $ua) ? 'mobile' : 'desktop',
            'visitor_hash' => hash('sha256', $sid),
            'ip_hash' => hash('sha256', $request->ip().'|'.config('app.key')),
            'user_agent' => $ua !== '' ? mb_substr($ua, 0, 255) : null,
        ];
    }

    /** referrer 도메인으로 유입 경로 분류 */
    private function source(?string $refHost, Request $request): string
    {
        if (! $refHost || str_contains($refHost, $request->getHost())) {
            return 'direct';
        }
        $map = [
            'naver' => 'naver', 'google' => 'google', 'daum' => 'daum',
            'instagram' => 'instagram', 'facebook' => 'facebook', 'fb.' => 'facebook',
            'youtube' => 'youtube', 'youtu.be' => 'youtube',
        ];
        foreach ($map as $needle => $source) {
            if (str_contains($refHost, $needle)) {
                return $source;
            }
        }

        return 'referral';
    }
}
