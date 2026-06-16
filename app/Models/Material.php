<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $fillable = [
        'type', 'code', 'name', 'category', 'unit', 'spec', 'note', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const TYPES = [
        'extra' => '추가 품목 재료',
        'etc' => '기타 재료',
    ];

    public const CODE_PREFIX = 'M';

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (empty($m->code)) {
                $m->code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        $prefix = self::CODE_PREFIX;
        $last = self::where('code', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(code, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->value('code');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%05d', $prefix, $next);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function scopeOfType($q, string $type)
    {
        return $q->where('type', $type);
    }
}
