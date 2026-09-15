<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 메인 페이지 팝업(관리자 설정). 단일 행으로 운영한다.
 */
class Popup extends Model
{
    protected $fillable = [
        'is_active', 'title', 'body', 'image', 'contact', 'link_url', 'link_label',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** 관리자 편집용 — 없으면 새 인스턴스(기본값). */
    public static function current(): self
    {
        return static::query()->first() ?? new self;
    }

    /** 메인에 노출할 활성 팝업(없으면 null). */
    public static function forHome(): ?self
    {
        return static::query()->where('is_active', true)->latest('updated_at')->first();
    }
}
