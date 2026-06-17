<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 본사 → 매장/공급처 포털 공지사항.
 */
class PortalNotice extends Model
{
    protected $fillable = [
        'title', 'content', 'audience', 'is_pinned', 'created_by',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    public const AUDIENCES = [
        'all' => '전체',
        'store' => '매장',
        'supplier' => '공급처',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAudienceLabelAttribute(): string
    {
        return self::AUDIENCES[$this->audience] ?? $this->audience;
    }

    /** 해당 역할(store/supplier)에게 보이는 공지 */
    public function scopeForRole($q, string $role)
    {
        return $q->whereIn('audience', ['all', $role]);
    }

    public function scopeSorted($q)
    {
        return $q->orderByDesc('is_pinned')->orderByDesc('id');
    }

    /** audience에 해당하는 대상 역할 목록 */
    public function targetRoles(): array
    {
        return $this->audience === 'all' ? ['store', 'supplier'] : [$this->audience];
    }
}
