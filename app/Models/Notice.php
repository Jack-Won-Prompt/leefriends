<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $fillable = [
        'category', 'title', 'content', 'is_pinned', 'views', 'published_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'views' => 'integer',
        'published_at' => 'datetime',
    ];

    public const CATEGORIES = [
        'notice' => '공지',
        'news' => '뉴스',
        'event' => '이벤트',
    ];

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }
}
