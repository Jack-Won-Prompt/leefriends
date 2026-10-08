<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 레시피 — 본사가 물품별로 이미지+글로 등록, 매장이 확인.
 */
class Recipe extends Model
{
    protected $fillable = [
        'supply_product_id', 'product_name', 'title', 'content', 'image', 'created_by',
    ];

    public function product()
    {
        return $this->belongsTo(SupplyProduct::class, 'supply_product_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** 이미지 전체 URL (없으면 null) */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset($this->image) : null;
    }

    public function scopeSorted($q)
    {
        return $q->orderByDesc('id');
    }
}
