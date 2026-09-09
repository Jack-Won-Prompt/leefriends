<?php

namespace App\Market\Services\Analysis;

use App\Market\Models\Region;
use App\Market\Models\RegionBoundary;
use App\Market\Support\Geometry;
use Illuminate\Support\Collection;

/**
 * 분석 범위(반경 또는 행정동 선택)를 "행정동 코드 → 가중치" 목록으로 바꾼다.
 *
 * 반경 분석의 원은 행정동 경계를 가로지르므로 행정동 통계를 그대로 더하면 과대집계된다.
 * 각 행정동이 원에 얼마나 걸치는지를 0~1 가중치로 환산해 통계를 안분한다.
 *
 * 계산 방식은 두 가지다.
 *  1) 경계 폴리곤이 있으면 원 안에 격자점을 뿌려 각 점이 어느 행정동에 속하는지 세는 방식(기본)
 *  2) 경계가 없으면 행정동을 "면적이 같은 원"으로 보고 원-원 교집합 면적으로 근사
 */
class RegionResolver
{
    /** 면적 정보가 없는 행정동에 적용할 기본 면적 (km²) */
    private const FALLBACK_AREA_KM2 = 1.6;

    /** 원 한 변을 몇 칸으로 쪼개 표본을 뽑을지 (61×61 → 원 안 약 2,900점) */
    private const GRID_STEPS = 61;

    /**
     * 분석 범위 중 이 비율보다 적게 차지하는 행정동은 스쳐 지나가는 것으로 보고 제외한다.
     *
     * "행정동의 몇 %" 가 아니라 "그린 범위의 몇 %" 다.
     * 앞의 기준을 쓰면 작게 그린 상권일수록 모든 행정동이 걸러져 버린다.
     * (반경 150m 를 1km² 짜리 동에 그리면 동 기준으로는 7% 지만 범위 기준으로는 100% 다)
     */
    private const MIN_SHARE = 0.02;

    /**
     * 반대로, 범위가 아주 넓으면 통째로 들어간 작은 행정동도 지분이 2% 를 밑돈다.
     * 그 동은 스쳐 간 것이 아니라 다 들어온 것이므로 살려야 한다.
     */
    private const MIN_WEIGHT = 0.2;

    /** 스쳐 지나가는 행정동인가 — 범위에서도 작고, 그 동에서도 작을 때만 버린다. */
    private static function isGraze(float $share, float $weight): bool
    {
        return $share < self::MIN_SHARE && $weight < self::MIN_WEIGHT;
    }

    /**
     * @return Collection<int, array{region: Region, weight: float, distance_km: float}>
     */
    public function fromRadius(float $lat, float $lng, int $radiusM): Collection
    {
        $radiusKm = $radiusM / 1000;

        $candidates = Region::withinRadius($lat, $lng, $radiusM)->get();

        if ($candidates->isEmpty()) {
            return collect();
        }

        $boundaries = RegionBoundary::whereIn('region_code', $candidates->pluck('code'))->get();

        $sampled = $boundaries->count() >= 1
            ? $this->weightsByGridSampling($candidates, $boundaries, $lat, $lng, $radiusKm)
            : ['weights' => [], 'shares' => []];

        $weights = $sampled['weights'];
        $circleArea = M_PI * $radiusKm ** 2;

        $resolved = $candidates
            ->map(function (Region $region) use ($sampled, $radiusKm, $circleArea) {
                $distanceKm = (float) ($region->distance_km ?? 0);
                $dongArea = $region->area_km2 ?: self::FALLBACK_AREA_KM2;

                $weight = $sampled['weights'][$region->code]
                    ?? $this->overlapWeight($distanceKm, $radiusKm, $region->area_km2);

                // 경계가 없어 근사원으로 구한 경우에도 "원의 몇 %" 를 되짚어 낸다.
                $share = $sampled['shares'][$region->code]
                    ?? min(1.0, $weight * $dongArea / max(1e-9, $circleArea));

                return [
                    'region' => $region,
                    'share' => round($share, 4),
                    'weight' => round($weight, 4),
                    'distance_km' => round($distanceKm, 3),
                ];
            })
            ->reject(fn (array $item) => self::isGraze($item['share'], $item['weight']))
            ->sortByDesc('share')
            ->values();

        return $weights === []
            ? $this->normalizeToCircle($resolved, $circleArea)
            : $resolved;
    }

    /**
     * 임의의 폴리곤(원·사각형·다각형)으로 상권을 잡는다.
     *
     * 반경은 원이라는 특수한 폴리곤일 뿐이라 계산은 같다.
     * 폴리곤 bounding box 에 격자점을 뿌려, 폴리곤 안에 든 점이 어느 행정동에
     * 떨어지는지 세어 겹침 비율을 구한다.
     *
     * @param  array<int, array{0: float, 1: float}>  $ring  [[lng, lat], ...]
     * @return Collection<int, array{region: Region, weight: float, distance_km: float}>
     */
    public function fromPolygon(array $ring): Collection
    {
        if (count($ring) < 3) {
            return collect();
        }

        [$minLng, $minLat, $maxLng, $maxLat] = Geometry::bbox($ring);
        [$centerLng, $centerLat] = Geometry::centroid($ring);

        /*
         * 후보는 경계 bounding box 가 겹치는 행정동으로 고른다.
         *
         * 행정동을 "면적이 같은 원" 으로 근사해 고르면, 길쭉한 동에서는 정작 그 동 안의
         * 점이 후보에서 빠진다. 실제로 시청 앞 7m 짜리 상권이 명동 안인데도
         * 소공동만 후보로 잡혀 결과가 0곳이 됐다.
         * 경계가 없는 행정동(전국 3곳)만 근사원으로 함께 훑는다.
         */
        $boundaries = RegionBoundary::intersecting($minLat, $maxLat, $minLng, $maxLng)->get()->keyBy('region_code');
        $codes = collect($this->candidateCodes($ring));

        if ($codes->isEmpty()) {
            return collect();
        }

        $candidates = Region::whereIn('code', $codes->all())->get();
        $areaKm2 = Geometry::areaM2($ring) / 1_000_000;

        $hits = [];
        $inside = 0;

        $stepLng = ($maxLng - $minLng) / (self::GRID_STEPS - 1);
        $stepLat = ($maxLat - $minLat) / (self::GRID_STEPS - 1);

        for ($i = 0; $i < self::GRID_STEPS; $i++) {
            $pointLat = $minLat + $i * $stepLat;

            for ($j = 0; $j < self::GRID_STEPS; $j++) {
                $pointLng = $minLng + $j * $stepLng;

                if (! Geometry::contains($ring, $pointLng, $pointLat)) {
                    continue;
                }

                $inside++;

                foreach ($candidates as $region) {
                    $boundary = $boundaries->get($region->code);

                    if ($boundary && $boundary->contains($pointLng, $pointLat)) {
                        $hits[$region->code] = ($hits[$region->code] ?? 0) + 1;

                        break;
                    }
                }
            }
        }

        if ($inside === 0) {
            return collect();
        }

        return $candidates
            ->map(function (Region $region) use ($hits, $inside, $areaKm2, $centerLat, $centerLng) {
                $count = $hits[$region->code] ?? 0;
                $dongArea = $region->area_km2 ?: self::FALLBACK_AREA_KM2;

                // share = 그린 범위 중 이 행정동이 차지하는 비율
                // weight = 그 땅이 행정동 전체에서 차지하는 비율 (통계 안분에 쓴다)
                $share = $count / $inside;

                return [
                    'region' => $region,
                    'share' => round($share, 4),
                    'weight' => round(min(1.0, $share * $areaKm2 / $dongArea), 4),
                    'distance_km' => round(
                        Geometry::distanceKm($centerLat, $centerLng, (float) $region->lat, (float) $region->lng),
                        3
                    ),
                ];
            })
            ->reject(fn (array $item) => self::isGraze($item['share'], $item['weight']))
            ->sortByDesc('share')
            ->values();
    }

    /**
     * 그린 범위에 닿을 수 있는 행정동 코드.
     *
     * 결과에 남는 행정동보다 넓은 집합이다. 범위를 스쳐 지나가는 동은 통계에서는 빼지만,
     * 그 동에 있는 점포가 범위 안에 들어와 있을 수는 있어 점포를 셀 때는 이 집합을 쓴다.
     *
     * @param  array<int, array{0: float, 1: float}>  $ring
     * @return array<int, string>
     */
    public function candidateCodes(array $ring): array
    {
        if (count($ring) < 3) {
            return [];
        }

        [$minLng, $minLat, $maxLng, $maxLat] = Geometry::bbox($ring);
        [$centerLng, $centerLat] = Geometry::centroid($ring);

        $byBbox = RegionBoundary::intersecting($minLat, $maxLat, $minLng, $maxLng)->pluck('region_code');

        // 경계가 없는 행정동(전국 3곳)은 근사원으로 함께 훑는다.
        $coverKm = Geometry::distanceKm($minLat, $minLng, $maxLat, $maxLng) / 2;
        $nearby = Region::withinRadius($centerLat, $centerLng, (int) ceil($coverKm * 1000))->pluck('code');

        return $byBbox->merge($nearby)->unique()->map(fn ($code) => (string) $code)->values()->all();
    }

    /**
     * @param  array<int, string>  $codes
     * @return Collection<int, array{region: Region, weight: float, distance_km: float}>
     */
    public function fromCodes(array $codes): Collection
    {
        return Region::whereIn('code', $codes)
            ->orderBy('full_name')
            ->get()
            ->map(fn (Region $region) => [
                'region' => $region,
                'weight' => 1.0,
                'distance_km' => 0.0,
            ]);
    }

    /**
     * @param  Collection<int, array{region: Region, weight: float}>  $resolved
     * @return array<string, float> 행정동코드 => 가중치
     */
    public function weightMap(Collection $resolved): array
    {
        return $resolved
            ->mapWithKeys(fn (array $item) => [$item['region']->code => $item['weight']])
            ->all();
    }

    /**
     * 원 안에 격자점을 뿌리고 각 점이 속한 행정동을 세어 겹침 비율을 구한다.
     *
     *   가중치 = (그 행정동에 떨어진 점 수 / 원 안 전체 점 수) × 원 면적 / 행정동 면적
     *
     * 비율(share) 은 "원의 몇 %가 이 행정동인가" 로, 스쳐 가는 동을 거를 때 쓴다.
     *
     * @param  Collection<int, Region>  $candidates
     * @param  Collection<int, RegionBoundary>  $boundaries
     * @return array{weights: array<string, float>, shares: array<string, float>}
     */
    private function weightsByGridSampling(
        Collection $candidates,
        Collection $boundaries,
        float $lat,
        float $lng,
        float $radiusKm
    ): array {
        $byCode = $boundaries->keyBy('region_code');
        $latPerKm = 1 / 110.574;
        $lngPerKm = 1 / max(0.000001, 111.320 * cos(deg2rad($lat)));

        $step = (2 * $radiusKm) / (self::GRID_STEPS - 1);
        $hits = [];
        $inside = 0;

        for ($i = 0; $i < self::GRID_STEPS; $i++) {
            $dy = -$radiusKm + $i * $step;

            for ($j = 0; $j < self::GRID_STEPS; $j++) {
                $dx = -$radiusKm + $j * $step;

                if ($dx * $dx + $dy * $dy > $radiusKm * $radiusKm) {
                    continue;
                }

                $inside++;
                $pointLat = $lat + $dy * $latPerKm;
                $pointLng = $lng + $dx * $lngPerKm;

                foreach ($candidates as $region) {
                    $boundary = $byCode->get($region->code);

                    if ($boundary && $boundary->contains($pointLng, $pointLat)) {
                        $hits[$region->code] = ($hits[$region->code] ?? 0) + 1;

                        break;
                    }
                }
            }
        }

        if ($inside === 0) {
            return ['weights' => [], 'shares' => []];
        }

        $circleArea = M_PI * $radiusKm ** 2;
        $weights = [];
        $shares = [];

        foreach ($candidates as $region) {
            $count = $hits[$region->code] ?? 0;

            if ($count === 0) {
                continue;
            }

            $area = $region->area_km2 ?: self::FALLBACK_AREA_KM2;
            $share = $count / $inside;

            $shares[$region->code] = $share;
            $weights[$region->code] = min(1.0, $share * $circleArea / $area);
        }

        return ['weights' => $weights, 'shares' => $shares];
    }

    /**
     * 근사원 방식은 실제 경계와 달라 겹침 면적의 합이 분석 원의 면적과 어긋난다.
     * 가중치를 비례 조정해 "분석 원 넓이만큼의 땅"을 정확히 집계하도록 맞춘다.
     *
     * @param  Collection<int, array{region: Region, weight: float, distance_km: float}>  $resolved
     * @return Collection<int, array{region: Region, weight: float, distance_km: float}>
     */
    private function normalizeToCircle(Collection $resolved, float $circleAreaKm2): Collection
    {
        $covered = $resolved->sum(
            fn (array $item) => $item['weight'] * ($item['region']->area_km2 ?: self::FALLBACK_AREA_KM2)
        );

        if ($covered <= 0) {
            return $resolved;
        }

        $scale = $circleAreaKm2 / $covered;

        return $resolved->map(function (array $item) use ($scale) {
            $item['weight'] = min(1.0, round($item['weight'] * $scale, 4));

            return $item;
        });
    }

    /**
     * 반지름 R(분석원)과 반지름 r(행정동 근사원)이 중심거리 d 만큼 떨어져 있을 때
     * 교집합 면적을 행정동 면적으로 나눈 값.
     */
    private function overlapWeight(float $distanceKm, float $radiusKm, ?float $areaKm2): float
    {
        $area = $areaKm2 && $areaKm2 > 0 ? $areaKm2 : self::FALLBACK_AREA_KM2;
        $r = sqrt($area / M_PI);
        $R = $radiusKm;
        $d = max(0.0, $distanceKm);

        if ($d >= $r + $R) {
            return 0.0;
        }

        if ($d <= abs($R - $r)) {
            // 한쪽이 다른 쪽에 완전히 포함된다.
            return min(1.0, (M_PI * min($r, $R) ** 2) / $area);
        }

        $a = $r ** 2 * acos(max(-1.0, min(1.0, ($d ** 2 + $r ** 2 - $R ** 2) / (2 * $d * $r))));
        $b = $R ** 2 * acos(max(-1.0, min(1.0, ($d ** 2 + $R ** 2 - $r ** 2) / (2 * $d * $R))));
        $c = 0.5 * sqrt(max(0.0, (-$d + $r + $R) * ($d + $r - $R) * ($d - $r + $R) * ($d + $r + $R)));

        return min(1.0, ($a + $b - $c) / $area);
    }
}
