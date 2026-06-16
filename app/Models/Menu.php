<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'category', 'name', 'name_en', 'description', 'price',
        'image', 'badge', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'integer',
        'sort_order' => 'integer',
    ];

    public const CATEGORIES = [
        'signature' => '시그니처',
        'bingsu' => '빙수',
        'drink' => '음료',
        'dessert' => '디저트',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
