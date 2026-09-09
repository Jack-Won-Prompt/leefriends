<?php

namespace App\Market\Support;

/**
 * 해당 지역 '망고정'(망고 디저트·빙수 카페) 프랜차이즈 개점 장단점 분석기.
 *
 * 상권 리포트 payload($report) + 운영 입력($plan: 홀 테이블 수·쿠팡잇츠·배민 연계)을
 * 종합해 장점/단점/운영 코멘트/종합 등급을 만든다. 미수록 지표는 판단에서 제외한다.
 * 순수 로직(DB 접근 없음) — 웹 화면과 PDF 가 동일 결과를 공유한다.
 */
class MangoFranchiseAdvisor
{
    /**
     * @param  array<string,mixed>  $report  분석 payload
     * @param  array<string,mixed>  $plan    ['hall_tables'=>?int,'coupang'=>bool,'baemin'=>bool]
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
            $pros[] = ['title' => '풍부한 유동인구', 'detail' => '길단위 유동인구 약 '.self::n($m['floating']).'명 규모로, 디저트·음료 충동 소비를 받쳐 줄 통행량이 충분합니다.'];
        }
        if ($m['female_share'] !== null && $m['female_share'] >= 50) {
            $pros[] = ['title' => '여성 고객 비중 우위', 'detail' => '유동인구 중 여성 비중이 '.self::p($m['female_share']).'로, 디저트·빙수 카페의 핵심 고객층과 부합합니다.'];
        }
        if ($m['peak_label']) {
            $pros[] = ['title' => '소비 피크 시간대 부합', 'detail' => '유동 피크가 '.$m['peak_label'].'로, 디저트 수요가 몰리는 시간대와 겹칩니다.'];
        }
        if ($m['top_segment']) {
            $pros[] = ['title' => '핵심 소비층 존재', 'detail' => '카드매출 최대 소비층이 '.$m['top_segment'].'로, 디저트 카페 주 타깃과 일치합니다.'];
        }
        if ($m['students'] >= 1000) {
            $pros[] = ['title' => '학생·젊은 층 밀집', 'detail' => '반경 내 학생 약 '.self::n($m['students']).'명으로 10~20대 유입 기반이 넓습니다.'];
        }
        if ($m['households'] >= 5000 || $m['workplace'] >= 5000) {
            $pros[] = ['title' => '탄탄한 배후 수요', 'detail' => '배후세대 '.self::n($m['households']).'세대·직장인구 '.self::n($m['workplace']).'명 규모로 상시 수요와 배달 기반이 큽니다.'];
        }
        if ($m['cafe_count'] > 0 && $m['cafe_share'] < 6) {
            $pros[] = ['title' => '카페·디저트 여지', 'detail' => '카페·디저트 점포 비중이 '.self::p($m['cafe_share']).'로 아직 과밀하지 않아 진입 여지가 있습니다.'];
        }

        // ── 상권 기반 단점 ──
        if ($m['covered']['floating'] && $m['floating'] < 20000) {
            $cons[] = ['title' => '유동인구 부족', 'detail' => '길단위 유동인구가 약 '.self::n($m['floating']).'명으로 통행량이 낮아 매장 방문 유입이 제한적일 수 있습니다.'];
        }
        if ($m['cafe_count'] > 0 && $m['cafe_share'] >= 6) {
            $cons[] = ['title' => '카페·디저트 과포화', 'detail' => '카페·디저트 점포가 '.self::n($m['cafe_count']).'곳(전체의 '.self::p($m['cafe_share']).')으로 경쟁이 치열합니다. 차별화 전략이 필요합니다.'];
        }
        if (! empty($m['cafe_brands'])) {
            $top = array_slice($m['cafe_brands'], 0, 3);
            $names = implode(', ', array_map(fn ($b) => $b['name'].'('.self::n($b['count']).')', $top));
            $cons[] = ['title' => '대형 카페 프랜차이즈 경쟁', 'detail' => '인근 다점포 카페 브랜드: '.$names.'. 가격·접근성 경쟁이 예상됩니다.'];
        }
        if (! empty($m['rival_desserts'])) {
            $cons[] = ['title' => '디저트 직접 경쟁 브랜드', 'detail' => '유사 디저트·빙수 브랜드 확인: '.implode(', ', array_slice($m['rival_desserts'], 0, 5)).'.'];
        }
        if (! $m['covered']['floating'] && ! $m['covered']['sales']) {
            $cons[] = ['title' => '수요 데이터 미수록', 'detail' => '이 지역은 유동인구·카드매출 공개 데이터가 없어 정밀 수요 추정이 제한됩니다(점포 분포 기준으로만 판단).'];
        }

        // ── 운영 입력(홀/배달) 종합 코멘트 ──
        $planNotes = self::planNotes($plan, $m);

        // ── 종합 등급 ──
        $grade = self::grade(count($pros), count($cons), $plan, $m);
        $summary = self::summary($grade, $m, $plan);

        return compact('pros', 'cons', 'planNotes', 'summary', 'grade', 'plan') + ['metrics' => $m];
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

        // 카페·디저트 섹터
        $cafeCount = 0;
        $cafeShare = 0.0;
        foreach ($r['stores']['by_sector'] ?? [] as $s) {
            if (($s['code'] ?? '') === 'cafe_dessert') {
                $cafeCount = (int) ($s['count'] ?? 0);
                $cafeShare = (float) ($s['share'] ?? 0);
                break;
            }
        }

        // 카페 프랜차이즈 브랜드 + 디저트 직접경쟁
        $cafeBrands = [];
        $rivalDesserts = [];
        $rivalKeywords = ['빙수', '설빙', '망고', '요거트', '디저트', '베이커리', '케이크', '와플', '젤라', '아이스크림'];
        foreach ($r['stores']['brands'] ?? [] as $b) {
            $name = (string) ($b['name'] ?? '');
            if (($b['sector'] ?? '') === 'cafe_dessert') {
                $cafeBrands[] = ['name' => $name, 'count' => (int) ($b['count'] ?? 0)];
            }
            foreach ($rivalKeywords as $kw) {
                if ($name !== '' && str_contains($name, $kw)) { $rivalDesserts[] = $name; break; }
            }
        }
        $rivalDesserts = array_values(array_unique($rivalDesserts));

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
            'cafe_brands' => $cafeBrands,
            'rival_desserts' => $rivalDesserts,
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

        return [
            'hall_tables' => ($tables === null || $tables === '') ? null : max(0, (int) $tables),
            'coupang' => (bool) ($plan['coupang'] ?? false),
            'baemin' => (bool) ($plan['baemin'] ?? false),
            'configured' => array_key_exists('configured', $plan)
                ? (bool) $plan['configured']
                : (isset($plan['hall_tables']) || isset($plan['coupang']) || isset($plan['baemin'])),
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

        // 홀 테이블
        if ($t === null) {
            $notes[] = ['tone' => 'info', 'title' => '홀 규모 미입력', 'detail' => '홀 테이블 수를 입력하면 체류형/테이크아웃 적합도를 판단합니다.'];
        } elseif ($t >= 10) {
            $notes[] = $dineInDemand
                ? ['tone' => 'pro', 'title' => '체류형 매장 유리', 'detail' => '홀 '.$t.'테이블 규모는 유동·여성·학생 수요가 받쳐 주는 이 상권에서 체류형 디저트 카페로 시너지가 큽니다.']
                : ['tone' => 'con', 'title' => '홀 과다 위험', 'detail' => '홀 '.$t.'테이블은 임대료·인건비 부담이 큰데, 이 상권의 체류 수요 신호는 약합니다. 좌석 축소를 검토하세요.'];
        } elseif ($t <= 4) {
            $notes[] = $deliveryOn
                ? ['tone' => 'pro', 'title' => '테이크아웃·배달형 적합', 'detail' => '홀 '.$t.'테이블의 소형 매장은 배달 연계와 결합해 소형 입지·높은 임대료 지역에서 효율적입니다.']
                : ['tone' => 'con', 'title' => '매출 채널 취약', 'detail' => '홀 '.$t.'테이블로 좌석이 적은데 배달 연계도 없어, 확보 가능한 매출 채널이 좁습니다.'];
        } else {
            $notes[] = ['tone' => 'info', 'title' => '균형형 홀 규모', 'detail' => '홀 '.$t.'테이블은 매장·포장·배달을 병행하기 무난한 규모입니다.'];
        }

        // 배달 연계
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
        $parts[] = '해당 지역 망고정(망고 디저트·빙수 카페) 개점 종합 판단은 「'.$grade['label'].'」(적합도 '.$grade['score'].'점)입니다.';

        if ($m['covered']['floating'] || $m['covered']['sales']) {
            $parts[] = '유동인구·소비 데이터를 반영했으며,';
        } else {
            $parts[] = '이 지역은 유동·매출 공개데이터가 없어 점포 분포 중심으로 판단했으며,';
        }

        if ($m['cafe_count'] > 0) {
            $parts[] = '카페·디저트 점포 '.self::n($m['cafe_count']).'곳('.self::p($m['cafe_share']).')과의 경쟁을 고려해야 합니다.';
        }

        if ($plan['configured']) {
            $t = $plan['hall_tables'] === null ? '미입력' : $plan['hall_tables'].'테이블';
            $d = $plan['coupang'] && $plan['baemin'] ? '쿠팡잇츠·배민 연계' : ($plan['coupang'] ? '쿠팡잇츠 연계' : ($plan['baemin'] ? '배민 연계' : '배달 미연계'));
            $parts[] = '운영 조건(홀 '.$t.' · '.$d.')을 반영했습니다.';
        } else {
            $parts[] = '홀 테이블 수와 배달 연계 여부를 입력하면 운영 방식까지 반영한 정밀 판단을 제공합니다.';
        }

        return implode(' ', $parts);
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
