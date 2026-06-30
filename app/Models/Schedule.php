<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 본사 일정(캘린더) — 날짜별 일정/내용.
 */
class Schedule extends Model
{
    protected $fillable = ['role', 'store_id', 'supplier_id', 'schedule_date', 'title', 'content', 'color', 'created_by'];

    protected $casts = [
        'schedule_date' => 'date',
    ];

    public const COLORS = ['mango', 'sky', 'emerald', 'rose', 'violet', 'neutral'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOnDate($q, $date)
    {
        return $q->whereDate('schedule_date', $date);
    }

    /** 로그인 사용자의 소속(역할+조직) 일정만 */
    public function scopeForUser($q, $user)
    {
        $q->where('role', $user->role);
        if ($user->role === 'store') {
            $q->where('store_id', $user->store_id);
        } elseif ($user->role === 'supplier') {
            $q->where('supplier_id', $user->supplier_id);
        }

        return $q;
    }
}
