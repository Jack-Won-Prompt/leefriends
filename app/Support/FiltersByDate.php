<?php

namespace App\Support;

use Illuminate\Http\Request;

/** 목록 컨트롤러 날짜 기간 필터 (from/to → whereDate) */
trait FiltersByDate
{
    /** 요청에서 from/to 파싱 (역순이면 교환) */
    protected function dateRange(Request $request): array
    {
        $from = $request->query('from') ?: null;
        $to = $request->query('to') ?: null;
        if ($from && $to && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    /** 쿼리에 날짜 범위 적용 */
    protected function applyDateRange($query, ?string $from, ?string $to, string $column = 'created_at')
    {
        if ($from) {
            $query->whereDate($column, '>=', $from);
        }
        if ($to) {
            $query->whereDate($column, '<=', $to);
        }

        return $query;
    }
}
