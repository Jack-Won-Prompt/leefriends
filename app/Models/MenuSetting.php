<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuSetting extends Model
{
    protected $fillable = ['route', 'hidden'];

    protected $casts = ['hidden' => 'boolean'];

    /** 숨김 처리된 라우트명 배열 (요청당 1회 캐시) */
    public static function hiddenRoutes(): array
    {
        static $cache = null;
        if ($cache === null) {
            try {
                $cache = static::where('hidden', true)->pluck('route')->all();
            } catch (\Throwable $e) {
                $cache = [];   // 마이그레이션 전 등
            }
        }

        return $cache;
    }
}
