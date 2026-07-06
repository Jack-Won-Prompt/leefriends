<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\PageVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** 본사 포털 — 사이트 방문 분석 (방문수 · 페이지별 · 유입경로 · 방문이력) */
class VisitAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);

        $base = fn () => PageVisit::whereBetween('created_at', [$from, $to]);

        $total = $base()->count();
        $unique = $base()->distinct('visitor_hash')->count('visitor_hash');
        $today = PageVisit::whereDate('created_at', Carbon::today())->count();

        // 일별 추이 (빈 날짜 0 채움)
        $rawDaily = $base()->selectRaw('DATE(created_at) d, COUNT(*) c')->groupBy('d')->pluck('c', 'd');
        $daily = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $key = $d->toDateString();
            $daily[$key] = (int) ($rawDaily[$key] ?? 0);
        }

        // 페이지별 방문 순위
        $topPages = $base()->selectRaw('path, page_name, COUNT(*) c')
            ->groupBy('path', 'page_name')->orderByDesc('c')->limit(12)->get();

        // 유입 경로 분석
        $sources = $base()->selectRaw('source, COUNT(*) c')->groupBy('source')->orderByDesc('c')->get();

        // 디바이스
        $devices = $base()->selectRaw('device, COUNT(*) c')->groupBy('device')->pluck('c', 'device');

        // 최근 방문 이력
        $recent = PageVisit::whereBetween('created_at', [$from, $to])
            ->latest()->paginate(30)->withQueryString();

        return view('portal.hq.analytics.index', [
            'from' => $from, 'to' => $to,
            'total' => $total, 'unique' => $unique, 'today' => $today,
            'daily' => $daily, 'topPages' => $topPages, 'sources' => $sources,
            'devices' => $devices, 'recent' => $recent,
        ]);
    }

    /** 조회 기간 (기본: 최근 30일) */
    private function range(Request $request): array
    {
        try {
            $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : Carbon::today()->subDays(29);
            $to = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : Carbon::today()->endOfDay();
        } catch (\Throwable) {
            $from = Carbon::today()->subDays(29);
            $to = Carbon::today()->endOfDay();
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }
}
