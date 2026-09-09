<?php

namespace App\Market\Support;

use Illuminate\Support\Facades\DB;

/**
 * 분석 대상 지역의 '빙수 프랜차이즈' 점포 경쟁 집계.
 * 리프랜즈(망고 빙수) 개점 분석에서 경쟁은 빙수 전문 프랜차이즈 점포만 비교한다.
 * (카페·디저트 전반이 아니라 빙수 브랜드로 한정)
 */
class BingsuCompetition
{
    /** 빙수 전문 프랜차이즈로 인정할 브랜드 키워드(큐레이트) */
    private const KEYWORDS = ['빙수', '설빙', '옥루몽', '밀탑', '흑화당', '동빙고', '눈꽃'];

    /**
     * @param  list<string>  $regionCodes  행정동코드
     * @return array{total:int,brands:list<array{name:string,count:int}>,keywords:list<string>}
     */
    public static function forRegions(array $regionCodes): array
    {
        $codes = array_values(array_filter($regionCodes));
        if (empty($codes)) {
            return ['total' => 0, 'brands' => [], 'keywords' => self::KEYWORDS];
        }

        $rows = DB::connection('market')->table('stores')
            ->whereIn('region_code', $codes)
            ->whereNotNull('brand')
            ->where(function ($q) {
                foreach (self::KEYWORDS as $kw) {
                    $q->orWhere('brand', 'like', '%'.$kw.'%');
                }
            })
            ->select('brand', DB::raw('count(*) as c'))
            ->groupBy('brand')
            ->orderByDesc('c')
            ->get();

        $brands = [];
        $total = 0;
        foreach ($rows as $r) {
            $brands[] = ['name' => (string) $r->brand, 'count' => (int) $r->c];
            $total += (int) $r->c;
        }

        return ['total' => $total, 'brands' => $brands, 'keywords' => self::KEYWORDS];
    }
}
