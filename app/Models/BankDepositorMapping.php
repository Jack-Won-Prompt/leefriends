<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 입금자명 ↔ 매장 매핑.
 */
class BankDepositorMapping extends Model
{
    protected $fillable = ['corp_num', 'depositor_name', 'store_id'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    /** corp의 입금자명 → store_id 맵 (소문자·공백제거 정규화 키) */
    public static function mapFor(string $corpNum): array
    {
        return static::where('corp_num', $corpNum)->get()
            ->keyBy(fn ($m) => static::normalize($m->depositor_name))
            ->map(fn ($m) => $m->store_id)
            ->all();
    }

    public static function normalize(?string $name): string
    {
        return preg_replace('/\s+/', '', (string) $name);
    }
}
