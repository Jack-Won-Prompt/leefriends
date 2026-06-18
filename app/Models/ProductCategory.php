<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 품목 대분류(카테고리) 마스터 — 기준정보에서 관리.
 */
class ProductCategory extends Model
{
    protected $fillable = ['name', 'code', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function products()
    {
        return $this->hasMany(SupplyProduct::class, 'category', 'name');
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order')->orderBy('id');
    }

    /** 카테고리명 → 코드 (없으면 폴백 접두) */
    public static function codeFor(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        return static::where('name', $name)->value('code');
    }
}
