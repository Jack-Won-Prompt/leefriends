<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 본사 → 매장/공급처 포털 공지사항.
 */
class PortalNotice extends Model
{
    protected $fillable = [
        'title', 'content', 'audience', 'store_id', 'is_pinned', 'created_by',
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

    /** 단일 매장 타겟(store_id) — null이면 audience 전체 */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function getAudienceLabelAttribute(): string
    {
        return self::AUDIENCES[$this->audience] ?? $this->audience;
    }

    /** 대상 표기 (예: "매장 · 망고정 월계점" / "전체") */
    public function getTargetLabelAttribute(): string
    {
        if ($this->audience === 'store' && $this->store_id) {
            return '매장 · '.($this->store->name ?? "#{$this->store_id}");
        }

        return $this->audience_label;
    }

    /** 해당 역할(store/supplier)에게 보이는 공지 (매장은 전체/자기 매장만) */
    public function scopeForRole($q, string $role)
    {
        return $q->whereIn('audience', ['all', $role]);
    }

    /** 특정 사용자에게 보이는 공지 — 매장은 단일 매장 타겟도 반영 */
    public function scopeVisibleTo($q, User $user)
    {
        if ($user->role === 'store') {
            return $q->whereIn('audience', ['all', 'store'])
                ->where(fn ($w) => $w->whereNull('store_id')->orWhere('store_id', $user->store_id));
        }

        return $q->whereIn('audience', ['all', $user->role]);
    }

    public function isVisibleTo(User $user): bool
    {
        if (! in_array($this->audience, ['all', $user->role], true)) {
            return false;
        }
        if ($user->role === 'store' && $this->store_id && (int) $this->store_id !== (int) $user->store_id) {
            return false;
        }

        return true;
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

    /** 발송 대상 사용자 쿼리 — 단일 매장이면 그 매장 계정만 */
    public function targetUsers()
    {
        if ($this->audience === 'store' && $this->store_id) {
            return User::where('role', 'store')->where('store_id', $this->store_id);
        }

        return User::whereIn('role', $this->targetRoles());
    }
}
