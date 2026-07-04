<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    protected $fillable = [
        'external_id', 'title', 'url', 'thumbnail', 'summary',
        'posted_at', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'posted_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** 로컬 다운로드 경로면 asset(), 원격 URL 이면 그대로 */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail) {
            return null;
        }

        return Str::startsWith($this->thumbnail, ['http://', 'https://'])
            ? $this->thumbnail
            : asset($this->thumbnail);
    }
}
