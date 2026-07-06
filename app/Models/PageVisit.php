<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    protected $fillable = [
        'path', 'page_name', 'source', 'referrer', 'device',
        'visitor_hash', 'ip_hash', 'user_agent',
    ];

    /** 유입 경로 라벨 */
    public const SOURCE_LABELS = [
        'direct' => '직접 유입',
        'naver' => '네이버',
        'google' => '구글',
        'daum' => '다음',
        'instagram' => '인스타그램',
        'facebook' => '페이스북',
        'youtube' => '유튜브',
        'referral' => '기타 사이트',
    ];

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCE_LABELS[$this->source] ?? $this->source;
    }
}
