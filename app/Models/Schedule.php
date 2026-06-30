<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 본사 일정(캘린더) — 날짜별 일정/내용.
 */
class Schedule extends Model
{
    protected $fillable = ['schedule_date', 'title', 'content', 'color', 'created_by'];

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
}
