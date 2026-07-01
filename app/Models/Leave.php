<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 휴무 신청.
 */
class Leave extends Model
{
    protected $fillable = [
        'user_id', 'role', 'store_id', 'supplier_id',
        'leave_date', 'reason', 'status', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'leave_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public const STATUS_LABELS = [
        'pending' => '승인대기',
        'approved' => '승인',
        'rejected' => '반려',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function scopeForOrg($q, User $user)
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
