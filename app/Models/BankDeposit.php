<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 계좌 입금 거래 + 주문 대사 상태.
 */
class BankDeposit extends Model
{
    protected $fillable = [
        'corp_num', 'bank_code', 'account_number', 'tid', 'trade_date', 'trade_dt',
        'acc_in', 'acc_out', 'balance', 'depositor', 'remark', 'memo',
        'matched_order_id', 'confirmed_at',
    ];

    protected $casts = [
        'acc_in' => 'integer',
        'acc_out' => 'integer',
        'balance' => 'integer',
        'confirmed_at' => 'datetime',
    ];

    public function matchedOrder()
    {
        return $this->belongsTo(Order::class, 'matched_order_id');
    }

    public function isMatched(): bool
    {
        return ! is_null($this->matched_order_id);
    }
}
