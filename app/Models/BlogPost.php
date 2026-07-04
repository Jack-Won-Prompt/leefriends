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

    /** 로컬 다운로드 경로면 asset(), 원격 URL 이면 그대로. 썸네일 미저장 시 커밋된 파일로 폴백 */
    public function getThumbnailUrlAttribute(): ?string
    {
        if ($this->thumbnail) {
            return Str::startsWith($this->thumbnail, ['http://', 'https://'])
                ? $this->thumbnail
                : asset($this->thumbnail);
        }

        // 다운로드 실패 등으로 썸네일이 비어도, 배포된 images/blog/{external_id}.jpg|png 가 있으면 사용
        foreach (['jpg', 'png', 'jpeg', 'webp'] as $ext) {
            $rel = 'images/blog/' . $this->external_id . '.' . $ext;
            if (is_file(public_path($rel))) {
                return asset($rel);
            }
        }

        return null;
    }
}
