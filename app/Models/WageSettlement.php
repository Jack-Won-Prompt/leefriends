<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 아르바이트 급여 입금 처리(기간별).
 */
class WageSettlement extends Model
{
    protected $fillable = [
        'user_id', 'period_from', 'period_to', 'total_hours', 'total_amount',
        'status', 'paid_at', 'paid_by',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'total_hours' => 'decimal:2',
        'total_amount' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
