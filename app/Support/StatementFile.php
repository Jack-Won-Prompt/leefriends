<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/** 거래명세서 PDF 다운로드 파일명 규칙: 매장명_날짜(Ymd)_시퀀스(3자리).pdf */
class StatementFile
{
    public static function name(?string $storeName, $date, int $seq): string
    {
        $store = trim((string) $storeName);
        $store = $store !== '' ? $store : '매장';
        $store = preg_replace('/\s+/u', '', $store);            // 공백 제거
        $store = preg_replace('#[\\\\/:*?"<>|]+#u', '', $store); // 파일명 금지문자 제거

        $d = $date ? Carbon::parse($date)->format('Ymd') : now()->format('Ymd');

        return sprintf('%s_%s_%03d.pdf', $store, $d, max(1, $seq));
    }
}
