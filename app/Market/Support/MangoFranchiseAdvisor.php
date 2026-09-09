<?php

namespace App\Market\Support;

/**
 * 해당 지역 '리프랜즈'(망고 빙수 프랜차이즈) 개점 장단점 분석기.
 *
 * 상권 리포트 payload($report) + 운영 입력($plan: 홀 테이블 수·쿠팡잇츠·배민) + 빙수 경쟁($report['bingsu_competition'])을
 * 종합해 장점/단점/운영 코멘트/등급을 만든다. 경쟁 비교는 인근 '빙수 전문 프랜차이즈' 점포만 대상으로 한다.
 * 미수록 지표는 판단에서 제외. 순수 로직(DB 접근 없음).
 */
class MangoFranchiseAdvisor
{
    private const BRAND = '리프랜즈';
    private const BRAND_DESC = '리프랜즈(망고 빙수 프랜차이즈)';

    /** 주요 빙수 프랜차이즈 포지셔닝 참고(경쟁력 비교용) */
    private const BINGSU_REF = [
        '설빙' => ['position' => '국내 1위 빙수 프랜차이즈 · 인절미/팥빙수 등 전통 디저트', 'edge' => '리프랜즈는 망고 특화·트렌디 메뉴와 배달 강화로 젊은층 차별화'],
        '옥루몽' => ['position' => '프리미엄 과일빙수 · 카페형', 'edge' => '리프랜즈는 합리적 가격대의 망고 빙수로 접근성 우위'],
        '밀탑' => ['position' => '백화점 기반 프리미엄 빙수', 'edge' => '리프랜즈는 로드샵·배달로 접점 확대'],
        '흑화당' => ['position' => '흑당·디저트 전문', 'edge' => '리프랜즈는 빙수 카테고리 집중'],
        '동빙고' => ['position' => '전통 빙수·디저트', 'edge' => '리프랜즈는 망고 시즌 메뉴로 차별화'],
        '눈꽃' => ['position' => '눈꽃빙수 전문', 'edge' => '리프랜즈는 망고 프리미엄 라인으로 차별화'],
    ];

    /**
     * @param  array<string,mixed>  $report
     * @param  array<string,mixed>  $plan
     * @return array<string,mixed>
     */
    public static function analyze(array $report, array $plan = []): array
    {
        $m = self::metrics($report);
        $plan = self::normalizePlan($plan);

        $pros = [];
        $cons = [];

        // ── 상권 기반 장점 ──
        if ($m['floating'] >= 50000) {
            $pros[] = ['title' => '풍부한 유동인구', 'detail' => '길단위 유동인구 약 '.self::n($m['floating']).'명 규모로, 빙수·디저트 충동 소비를 받쳐 줄 통행량이 충분합니다.'];
        }
        if ($m['female_share'] !== null && $m['female_share'] >= 50) {
            $pros[] = ['title' => '여성 고객 비중 우위', 'detail' => '유동인구 중 여성 비중이 '.self::p($m['female_share']).'로, 빙수 카페의 핵심 고객층과 부합합니다.'];
        }
        if ($m['peak_label']) {
            $pros[] = ['title' => '소비 피크 시간대 부합', 'detail' => '유동 피크가 '.$m['peak_label'].'로, 빙수 수요가 몰리는 시간대와 겹칩니다.'];
        }
        if ($m['top_segment']) {
            $pros[] = ['title' => '핵심 소비층 존재', 'detail' => '카드매출 최대 소비층이 '.$m['top_segment'].'로, 빙수 카페 주 타깃과 일치합니다.'];
        }
        if ($m['students'] >= 1000) {
            $pros[] = ['title' => '학생·젊은 층 밀집', 'detail' => '반경 내 학생 약 '.self::n($m['students']).'명으로 10~20대 유입 기반이 넓습니다.'];
        }
        if ($m['households'] >= 5000 || $m['workplace'] >= 5000) {
            $pros[] = ['title' => '탄탄한 배후 수요', 'detail' => '배후세대 '.self::n($m['households']).'세대·직장인구 '.self::n($m['workplace']).'명 규모로 상시 수요와 배달 기반이 큽니다.'];
        }

        // ── 빙수 프랜차이즈 경쟁(빙수 점포만 비교) ──
        if ($m['bingsu_total'] === 0) {
            $pros[] = ['title' => '빙수 프랜차이즈 무경쟁', 'detail' => '인근에 설빙 등 빙수 전문 프랜차이즈 점포가 확인되지 않아, 빙수 카테고리를 선점할 기회가 큽니다.'];
        } elseif ($m['bingsu_total'] <= 2) {
            $names = self::brandList($m['bingsu_brands']);
            $pros[] = ['title' => '빙수 경쟁 낮음', 'detail' => '인근 빙수 프랜차이즈가 '.self::n($m['bingsu_total']).'곳'.($names ? '('.$names.')' : '').'에 불과해 경쟁 부담이 낮습니다.'];
        } else {
            $names = self::brandList($m['bingsu_brands']);
            $cons[] = ['title' => '빙수 프랜차이즈 경쟁', 'detail' => '인근 빙수 전문 프랜차이즈가 '.self::n($m['bingsu_total']).'곳'.($names ? ' — '.$names : '').'으로, 빙수 시장 경쟁이 치열합니다.'];
        }

        // ── 상권 기반 단점 ──
        if ($m['covered']['floating'] && $m['floating'] < 20000) {
            $cons[] = ['title' => '유동인구 부족', 'detail' => '길단위 유동인구가 약 '.self::n($m['floating']).'명으로 통행량이 낮아 매장 방문 유입이 제한적일 수 있습니다.'];
        }
        if (! $m['covered']['floating'] && ! $m['covered']['sales']) {
            $cons[] = ['title' => '수요 데이터 미수록', 'detail' => '이 지역은 유동인구·카드매출 공개 데이터가 없어 정밀 수요 추정이 제한됩니다(점포 분포 기준으로만 판단).'];
        }

        // ── 운영 입력(홀/배달) 종합 코멘트 ──
        $planNotes = self::planNotes($plan, $m);

        // ── 종합 등급 ──
        $grade = self::grade(count($pros), count($cons), $plan, $m);
        $summary = self::summary($grade, $m, $plan);

        $competitors = self::competitors($m['bingsu_brands']);

        return compact('pros', 'cons', 'planNotes', 'summary', 'grade', 'plan', 'competitors') + ['metrics' => $m];
    }

    /**
     * 타 빙수 프랜차이즈 경쟁력 비교표.
     * 자사(리프랜즈) + 인근 빙수 프랜차이즈(참고 포지셔닝 매핑) + 벤치마크(설빙).
     *
     * @param  list<array{name:string,count:int}>  $nearbyBrands
     * @return list<array<string,mixed>>
     */
    private static function competitors(array $nearbyBrands): array
    {
        $rows = [[
            'name' => self::BRAND,
            'self' => true,
            'nearby' => null,
            'position' => '망고 특화 빙수 · 트렌디 메뉴 · 테이크아웃/배달 병행',
            'edge' => '젊은층·여성 타깃, 시즌 망고 디저트로 차별화',
        ]];

        $seen = [];
        foreach ($nearbyBrands as $b) {
            $name = (string) $b['name'];
            $seen[$name] = true;
            $ref = self::BINGSU_REF[$name] ?? ['position' => '빙수·디저트 프랜차이즈', 'edge' => '리프랜즈는 망고 특화·배달 강화로 차별화'];
            $rows[] = ['name' => $name, 'self' => false, 'nearby' => (int) $b['count'], 'position' => $ref['position'], 'edge' => $ref['edge']];
        }

        // 벤치마크: 인근에 설빙이 없으면 국내 1위 브랜드를 참고로 추가
        if (! isset($seen['설빙'])) {
            $ref = self::BINGSU_REF['설빙'];
            $rows[] = ['name' => '설빙', 'self' => false, 'nearby' => 0, 'position' => $ref['position'], 'edge' => $ref['edge']];
        }

        return array_slice($rows, 0, 6);
    }

    /** @return array<string,mixed> */
    private static function metrics(array $r): array
    {
        $fga = $r['floating']['by_gender_age'] ?? [];
        $female = (int) ($fga['female'] ?? 0);
        $male = (int) ($fga['male'] ?? 0);
        $flowTotal = $female + $male;

        $peak = $r['floating']['peak'] ?? null;
        $peakLabel = null;
        if (is_array($peak) && ($peak['value'] ?? 0) > 0) {
            $dt = ($peak['day_type'] ?? '') === 'weekend' ? '주말' : '평일';
            $peakLabel = $dt.' '.self::timeBand($peak['time_band'] ?? '');
        }

        $topSeg = null;
        $ts = $r['sales']['top_segment'] ?? null;
        if (is_array($ts) && ($ts['value'] ?? 0) > 0) {
            $g = ($ts['gender'] ?? '') === 'F' ? '여성' : (($ts['gender'] ?? '') === 'M' ? '남성' : '');
            $topSeg = trim($g.' '.str_replace('s', '대', (string) ($ts['age_band'] ?? '')));
        }

        // 카페·디저트 섹터(참고용 맥락)
        $cafeCount = 0;
        $cafeShare = 0.0;
        foreach ($r['stores']['by_sector'] ?? [] as $s) {
            if (($s['code'] ?? '') === 'cafe_dessert') {
                $cafeCount = (int) ($s['count'] ?? 0);
                $cafeShare = (float) ($s['share'] ?? 0);
                break;
            }
        }

        // 빙수 프랜차이즈 경쟁(컨트롤러가 DB 로 집계해 주입)
        $bingsu = $r['bingsu_competition'] ?? ['total' => 0, 'brands' => []];

        return [
            'resident' => (int) ($r['resident']['total'] ?? 0),
            'workplace' => (int) ($r['workplace']['total'] ?? 0),
            'households' => (int) ($r['households']['total'] ?? 0),
            'floating' => $flowTotal,
            'female_share' => $flowTotal > 0 ? round($female / $flowTotal * 100, 1) : null,
            'peak_label' => $peakLabel,
            'top_segment' => $topSeg,
            'sales_amount' => (int) ($r['sales']['total_amount'] ?? 0),
            'stores_total' => (int) ($r['stores']['total'] ?? 0),
            'cafe_count' => $cafeCount,
            'cafe_share' => $cafeShare,
            'bingsu_total' => (int) ($bingsu['total'] ?? 0),
            'bingsu_brands' => $bingsu['brands'] ?? [],
            'students' => (int) ($r['education']['students']['total'] ?? 0),
            'covered' => [
                'floating' => $flowTotal > 0,
                'sales' => (float) ($r['sales']['total_amount'] ?? 0) > 0,
                'resident' => (int) ($r['resident']['total'] ?? 0) > 0,
            ],
        ];
    }

    /** @return array<string,mixed> */
    private static function normalizePlan(array $plan): array
    {
        $tables = $plan['hall_tables'] ?? null;
        $area = $plan['area_pyeong'] ?? null;

        return [
            'hall_tables' => ($tables === null || $tables === '') ? null : max(0, (int) $tables),
            'area_pyeong' => ($area === null || $area === '') ? null : max(0, (int) $area),
            'coupang' => (bool) ($plan['coupang'] ?? false),
            'baemin' => (bool) ($plan['baemin'] ?? false),
            'configured' => array_key_exists('configured', $plan)
                ? (bool) $plan['configured']
                : (isset($plan['hall_tables']) || isset($plan['area_pyeong']) || isset($plan['coupang']) || isset($plan['baemin'])),
        ];
    }

    /** @return list<array<string,string>> */
    private static function planNotes(array $plan, array $m): array
    {
        $notes = [];
        if (! $plan['configured']) {
            $notes[] = ['tone' => 'info', 'title' => '운영 조건 입력 필요', 'detail' => '홀 테이블 수·쿠팡잇츠·배민 연계 여부를 입력하면 매장 운영 방식까지 반영한 종합 판단을 제공합니다.'];

            return $notes;
        }

        $t = $plan['hall_tables'];
        $deliveryOn = $plan['coupang'] || $plan['baemin'];
        $deliveryBoth = $plan['coupang'] && $plan['baemin'];
        $dineInDemand = ($m['floating'] >= 50000) || ($m['female_share'] !== null && $m['female_share'] >= 50) || $m['students'] >= 1000;
        $deliveryDemand = ($m['households'] >= 5000) || ($m['workplace'] >= 5000) || ($m['resident'] >= 15000);

        if ($t === null) {
            $notes[] = ['tone' => 'info', 'title' => '홀 규모 미입력', 'detail' => '홀 테이블 수를 입력하면 체류형/테이크아웃 적합도를 판단합니다.'];
        } elseif ($t >= 10) {
            $notes[] = $dineInDemand
                ? ['tone' => 'pro', 'title' => '체류형 매장 유리', 'detail' => '홀 '.$t.'테이블 규모는 유동·여성·학생 수요가 받쳐 주는 이 상권에서 체류형 빙수 카페로 시너지가 큽니다.']
                : ['tone' => 'con', 'title' => '홀 과다 위험', 'detail' => '홀 '.$t.'테이블은 임대료·인건비 부담이 큰데, 이 상권의 체류 수요 신호는 약합니다. 좌석 축소를 검토하세요.'];
        } elseif ($t <= 4) {
            $notes[] = $deliveryOn
                ? ['tone' => 'pro', 'title' => '테이크아웃·배달형 적합', 'detail' => '홀 '.$t.'테이블의 소형 매장은 배달 연계와 결합해 소형 입지·높은 임대료 지역에서 효율적입니다.']
                : ['tone' => 'con', 'title' => '매출 채널 취약', 'detail' => '홀 '.$t.'테이블로 좌석이 적은데 배달 연계도 없어, 확보 가능한 매출 채널이 좁습니다.'];
        } else {
            $notes[] = ['tone' => 'info', 'title' => '균형형 홀 규모', 'detail' => '홀 '.$t.'테이블은 매장·포장·배달을 병행하기 무난한 규모입니다.'];
        }

        // 매장 평수
        $a = $plan['area_pyeong'];
        if ($a !== null) {
            if ($a >= 25) {
                $notes[] = ['tone' => 'info', 'title' => '여유 매장 면적', 'detail' => '매장 '.$a.'평은 체류형 좌석·빙수 진열 여력이 충분합니다. 다만 임대료·관리비 부담을 매출로 감당할 수 있는지 점검이 필요합니다.'.($t !== null && $t < 8 ? ' 면적 대비 홀 '.$t.'테이블은 적어 좌석 확충 여지가 있습니다.' : '')];
            } elseif ($a <= 10) {
                $notes[] = ['tone' => $deliveryOn ? 'pro' : 'con', 'title' => '소형 매장 면적', 'detail' => '매장 '.$a.'평 규모는 테이크아웃·배달 중심 운영이 적합합니다.'.($deliveryOn ? ' 배달 연계가 되어 있어 소형 면적을 효율적으로 활용할 수 있습니다.' : ' 배달 연계가 없으면 좁은 좌석만으로 매출이 제한됩니다.')];
            } else {
                $notes[] = ['tone' => 'info', 'title' => '표준 매장 면적', 'detail' => '매장 '.$a.'평은 소형 빙수 카페로 무난한 규모입니다.'];
            }
        }

        if ($deliveryBoth) {
            $notes[] = ['tone' => 'pro', 'title' => '배달 커버리지 최대', 'detail' => '쿠팡잇츠·배민 동시 연계로 배달 노출·주문 채널을 최대화합니다.'.($deliveryDemand ? ' 배후세대·직장 수요가 많아 배달 기여가 특히 큽니다.' : '')];
        } elseif ($deliveryOn) {
            $only = $plan['coupang'] ? '쿠팡잇츠' : '배민';
            $notes[] = ['tone' => 'info', 'title' => '배달 부분 연계', 'detail' => $only.'만 연계되어 있습니다. 나머지 플랫폼도 연계하면 배달 커버리지를 넓힐 수 있습니다.'];
        } else {
            $notes[] = ['tone' => 'con', 'title' => '배달 채널 부재', 'detail' => '쿠팡잇츠·배민 미연계로 배달 매출을 확보하지 못합니다.'.($deliveryDemand ? ' 배후세대·직장 수요가 큰 이 상권에서는 기회 손실이 큽니다.' : '')];
        }

        return $notes;
    }

    /** @return array{label:string,tone:string,score:int} */
    private static function grade(int $pros, int $cons, array $plan, array $m): array
    {
        $score = 50 + $pros * 10 - $cons * 12;
        if ($plan['configured']) {
            $score += ($plan['coupang'] || $plan['baemin']) ? 5 : -5;
            if ($plan['hall_tables'] !== null && $plan['hall_tables'] >= 5 && ($m['floating'] >= 50000 || $m['students'] >= 1000)) {
                $score += 5;
            }
        }
        $score = max(0, min(100, $score));

        if ($score >= 70) return ['label' => '개점 추천', 'tone' => 'good', 'score' => $score];
        if ($score >= 45) return ['label' => '조건부 검토', 'tone' => 'ok', 'score' => $score];

        return ['label' => '신중 검토', 'tone' => 'caution', 'score' => $score];
    }

    private static function summary(array $grade, array $m, array $plan): string
    {
        $parts = [];
        $parts[] = '해당 지역 '.self::BRAND_DESC.' 개점 종합 판단은 「'.$grade['label'].'」(적합도 '.$grade['score'].'점)입니다.';

        if ($m['covered']['floating'] || $m['covered']['sales']) {
            $parts[] = '유동인구·소비 데이터를 반영했으며,';
        } else {
            $parts[] = '이 지역은 유동·매출 공개데이터가 없어 점포 분포 중심으로 판단했으며,';
        }

        if ($m['bingsu_total'] === 0) {
            $parts[] = '인근에 빙수 전문 프랜차이즈가 없어 선점 여지가 큽니다.';
        } else {
            $parts[] = '인근 빙수 프랜차이즈 '.self::n($m['bingsu_total']).'곳과의 경쟁을 고려해야 합니다.';
        }

        if ($plan['configured']) {
            $t = $plan['hall_tables'] === null ? '미입력' : $plan['hall_tables'].'테이블';
            $area = $plan['area_pyeong'] === null ? '' : ' · '.$plan['area_pyeong'].'평';
            $d = $plan['coupang'] && $plan['baemin'] ? '쿠팡잇츠·배민 연계' : ($plan['coupang'] ? '쿠팡잇츠 연계' : ($plan['baemin'] ? '배민 연계' : '배달 미연계'));
            $parts[] = '운영 조건(홀 '.$t.$area.' · '.$d.')을 반영했습니다.';
        } else {
            $parts[] = '홀 테이블 수와 배달 연계 여부를 입력하면 운영 방식까지 반영한 정밀 판단을 제공합니다.';
        }

        return implode(' ', $parts);
    }

    private static function brandList(array $brands): string
    {
        $top = array_slice($brands, 0, 3);

        return implode(', ', array_map(fn ($b) => $b['name'].'('.self::n((int) $b['count']).')', $top));
    }

    private static function timeBand(string $b): string
    {
        return [
            'dawn' => '새벽', 'morning' => '아침', 'lunch' => '점심', 'afternoon' => '오후',
            'evening' => '저녁', 'night' => '밤',
        ][$b] ?? $b;
    }

    private static function n(int $v): string
    {
        return number_format($v);
    }

    private static function p(float $v): string
    {
        return rtrim(rtrim(number_format($v, 1), '0'), '.').'%';
    }
}
