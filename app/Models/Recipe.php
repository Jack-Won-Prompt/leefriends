<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 레시피 — 본사가 물품별로 이미지+영상+글로 등록, 매장이 확인.
 */
class Recipe extends Model
{
    protected $fillable = [
        'supply_product_id', 'product_name', 'title', 'content',
        'image', 'video', 'video_link', 'created_by',
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

    /** 업로드한 영상 파일의 전체 URL (없으면 null) */
    public function getVideoUrlAttribute(): ?string
    {
        return $this->video ? asset($this->video) : null;
    }

    /**
     * 외부 영상 링크를 <iframe>에 바로 넣을 수 있는 주소로 바꾼다.
     * 유튜브·Vimeo를 알아보고, 그 밖의 주소는 그대로 쓴다.
     */
    public function getVideoEmbedUrlAttribute(): ?string
    {
        $link = trim((string) $this->video_link);
        if ($link === '') {
            return null;
        }

        // 유튜브: watch?v=ID / youtu.be/ID / shorts/ID / embed/ID
        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{6,})~', $link, $m)) {
            return 'https://www.youtube.com/embed/'.$m[1];
        }

        // Vimeo: vimeo.com/123456789
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $link, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return $link;
    }

    /** 영상(업로드 또는 링크)이 있는지 */
    public function getHasVideoAttribute(): bool
    {
        return (bool) ($this->video || trim((string) $this->video_link) !== '');
    }

    public function scopeSorted($q)
    {
        return $q->orderByDesc('id');
    }
}
