<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 택배사 마스터 (본사 기준정보). 직접 배송 포함.
 */
class Courier extends Model
{
    protected $fillable = ['name', 'is_direct', 'is_active', 'sort_order'];

    protected $casts = [
        'is_direct' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order')->orderBy('id');
    }
}
