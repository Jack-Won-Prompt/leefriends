<?php

namespace App\Http\Controllers\Admin\Market;

use App\Market\Models\Analysis;
use App\Market\Models\Region;
use App\Market\Services\Analysis\AnalysisRunner;
use App\Market\Services\Analysis\StatisticsRepository;
use App\Market\Support\BingsuCompetition;
use App\Market\Support\Geometry;
use App\Market\Support\MangoAiAdvisor;
use App\Market\Support\MangoFranchiseAdvisor;
use App\Market\Support\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AnalysisController extends \App\Http\Controllers\Controller
{
    public function __construct(
        private readonly AnalysisRunner $runner,
        private readonly StatisticsRepository $stats,
    ) {}

    public function index(Request $request): View
    {
        return view('market.analyses.index', [
            'analyses' => \App\Market\Models\User::marketOwner()->analyses()->paginate(12),
        ]);
    }

    public function create(Request $request): View
    {
        $periods = $this->stats->availablePeriods();

        return view('market.analyses.create', [
            'radiusOptions' => config('map.radius_options'),
            'defaultRadius' => config('map.default_radius'),
            'defaultCenter' => config('map.default_center'),
            'periods' => $periods,
            'defaultPeriod' => $periods[0] ?? Period::month(now()->subMonth()->format('Ym')),
            'sidoList' => Region::query()->distinct()->orderBy('sido_name')->pluck('sido_name'),
            'favorites' => \App\Market\Models\User::marketOwner()->favoriteRegions()->with('region')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'mode' => ['required', Rule::in(['radius', 'region', 'polygon'])],
            'center_lat' => ['required_if:mode,radius', 'nullable', 'numeric', 'between:-90,90'],
            'center_lng' => ['required_if:mode,radius', 'nullable', 'numeric', 'between:-180,180'],
            'radius_m' => ['required_if:mode,radius', 'nullable', 'integer', 'min:100', 'max:5000'],
            'address' => ['nullable', 'string', 'max:200'],
            // 지도에 그린 상권. shape_ring 은 JSON 문자열로 온다.
            'shape_kind' => ['required_if:mode,polygon', 'nullable', Rule::in(['circle', 'rectangle', 'polygon'])],
            'shape_ring' => ['required_if:mode,polygon', 'nullable', 'string'],
            'area_m2' => ['nullable', 'integer', 'min:0'],
            'region_codes' => ['required_if:mode,region', 'nullable', 'array', 'max:30'],
            'region_codes.*' => ['string', 'exists:market.regions,code'],
            // 월(YYYYMM) 또는 분기(YYYYQ) 코드를 받는다.
            'period' => ['required', 'regex:/^(\d{6}|\d{4}[1-4])$/'],
        ], [
            'region_codes.required_if' => '분석할 행정동을 한 곳 이상 선택해 주세요.',
            'center_lat.required_if' => '지도를 클릭하거나 주소를 검색해 중심 지점을 지정해 주세요.',
            'shape_ring.required_if' => '지도에 상권을 먼저 그려 주세요.',
            'period.regex' => '기준 기간은 YYYYMM(월) 또는 YYYYQ(분기) 형식이어야 합니다.',
        ]);

        $period = Period::parse($validated['period']);
        $ring = $validated['mode'] === 'polygon'
            ? Geometry::normalizeRing(json_decode($validated['shape_ring'] ?? '[]', true) ?: [])
            : [];

        if ($validated['mode'] === 'polygon' && count($ring) < 3) {
            return back()->withInput()->withErrors(['shape_ring' => '상권 모양을 읽을 수 없습니다. 다시 그려 주세요.']);
        }

        $analysis = \App\Market\Models\User::marketOwner()->analyses()->create([
            'title' => $validated['title'],
            'mode' => $validated['mode'],
            'center_lat' => $validated['center_lat'] ?? null,
            'center_lng' => $validated['center_lng'] ?? null,
            'radius_m' => $validated['radius_m'] ?? null,
            'shape_kind' => $validated['mode'] === 'polygon' ? $validated['shape_kind'] : null,
            'shape_ring' => $ring ?: null,
            'area_m2' => $ring ? (int) round(Geometry::areaM2($ring)) : null,
            'address' => $validated['address'] ?? null,
            'region_codes' => $validated['region_codes'] ?? [],
            'status' => 'pending',
        ] + $period->columns());

        $this->runner->run($analysis);

        return redirect()->route('market.analyses.show', $analysis)
            ->with('status', '상권분석이 완료되었습니다.');
    }

    public function show(Request $request, Analysis $analysis): View
    {
        $this->authorizeOwner($request, $analysis);

        return view('market.analyses.show', [
            'analysis' => $analysis,
            'report' => $analysis->payload ?? [],
            'mango' => self::effectiveMango($analysis),
            'aiAvailable' => MangoAiAdvisor::available(),
        ]);
    }

    /** payload 에 빙수 프랜차이즈 경쟁(지역 스코프 DB 집계)을 주입한 리포트 */
    public static function reportWithBingsu(Analysis $analysis): array
    {
        $report = $analysis->payload ?? [];
        if (! isset($report['bingsu_competition'])) {
            $report['bingsu_competition'] = BingsuCompetition::forRegions($analysis->region_codes ?? []);
        }

        return $report;
    }

    /** 저장된 AI 분석이 있으면 우선 사용, 없으면 규칙 기반(빙수 경쟁 주입). */
    public static function effectiveMango(Analysis $analysis): array
    {
        $report = $analysis->payload ?? [];
        if (! empty($report['mango_ai'])) {
            return $report['mango_ai'] + ['ai' => true];
        }

        return MangoFranchiseAdvisor::analyze(self::reportWithBingsu($analysis), $report['mango_plan'] ?? []);
    }

    /** 망고정 개점 운영 조건(홀 테이블 수·쿠팡잇츠·배민) 저장 → payload 병합 + AI 재생성 */
    public function mangoPlan(Request $request, Analysis $analysis): RedirectResponse
    {
        $this->authorizeOwner($request, $analysis);

        $data = $request->validate([
            'hall_tables' => ['nullable', 'integer', 'min:0', 'max:500'],
            'area_pyeong' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'coupang' => ['nullable', 'boolean'],
            'baemin' => ['nullable', 'boolean'],
        ]);

        $payload = $analysis->payload ?? [];
        $payload['mango_plan'] = [
            'hall_tables' => $data['hall_tables'] ?? null,
            'area_pyeong' => $data['area_pyeong'] ?? null,
            'coupang' => $request->boolean('coupang'),
            'baemin' => $request->boolean('baemin'),
            'configured' => true,
        ];

        // 이미 AI 분석을 쓰던 화면이면 새 운영조건으로 자동 재생성(실패 시 조용히 규칙 기반 유지)
        $note = '';
        if (! empty($payload['mango_ai']) && MangoAiAdvisor::available()) {
            try {
                $payload['mango_ai'] = MangoAiAdvisor::generate(self::reportWithBingsu($analysis) + ['mango_plan' => $payload['mango_plan']], $payload['mango_plan']);
                $note = ' (AI 종합 분석 갱신)';
            } catch (\Throwable $e) {
                unset($payload['mango_ai']);   // 갱신 실패 → 규칙 기반으로
                $note = ' (AI 갱신 실패 — 기본 분석 표시)';
            }
        }
        $analysis->update(['payload' => $payload]);

        return redirect()->route('market.analyses.show', $analysis)
            ->with('status', '망고정 개점 운영 조건을 반영했습니다.'.$note);
    }

    /** 망고정 개점 장단점 — AI 종합 분석 생성/재생성 */
    public function mangoAi(Request $request, Analysis $analysis): RedirectResponse
    {
        $this->authorizeOwner($request, $analysis);

        if (! MangoAiAdvisor::available()) {
            return back()->with('error', 'AI 연동이 설정되지 않았습니다(ANTHROPIC/OPENAI 키).');
        }

        $payload = $analysis->payload ?? [];
        try {
            $payload['mango_ai'] = MangoAiAdvisor::generate(self::reportWithBingsu($analysis), $payload['mango_plan'] ?? []);
            $analysis->update(['payload' => $payload]);

            return redirect()->route('market.analyses.show', $analysis)
                ->with('status', 'AI 종합 분석을 생성했습니다.');
        } catch (\Throwable $e) {
            return back()->with('error', 'AI 분석 생성 실패: '.$e->getMessage());
        }
    }

    public function rerun(Request $request, Analysis $analysis): RedirectResponse
    {
        $this->authorizeOwner($request, $analysis);

        $latest = $this->stats->latestPeriod();

        if ($latest) {
            $analysis->update($latest->columns());
        }

        $this->runner->run($analysis);

        return redirect()->route('market.analyses.show', $analysis)
            ->with('status', '최신 데이터로 다시 분석했습니다.');
    }

    public function destroy(Request $request, Analysis $analysis): RedirectResponse
    {
        $this->authorizeOwner($request, $analysis);
        $analysis->delete();

        return redirect()->route('market.analyses.index')->with('status', '분석을 삭제했습니다.');
    }

    private function authorizeOwner(Request $request, Analysis $analysis): void
    {
        abort_unless($analysis->user_id === \App\Market\Models\User::marketOwner()->id, 403);
    }
}
