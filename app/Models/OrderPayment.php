<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 토스페이먼츠 결제 내역 (발주 1건에 결제 시도 N건).
 */
class OrderPayment extends Model
{
    protected $fillable = [
        'order_id', 'toss_order_id', 'payment_key', 'method', 'amount', 'status',
        'va_bank', 'va_account', 'va_due_at', 'approved_at', 'raw',
    ];

    protected $casts = [
        'amount' => 'integer',
        'va_due_at' => 'datetime',
        'approved_at' => 'datetime',
        'raw' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
