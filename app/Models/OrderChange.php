<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderChange extends Model
{
    protected $fillable = [
        'order_id', 'store_id', 'change_type', 'seller_type', 'supplier_id',
        'order_no', 'summary', 'acknowledged_at', 'acknowledged_by',
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];

    public const TYPES = [
        'updated' => '주문 수정',
        'canceled' => '주문 취소',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeForSeller($q, string $sellerType, ?int $supplierId = null)
    {
        $q->where('seller_type', $sellerType);
        if ($sellerType === 'supplier') {
            $q->where('supplier_id', $supplierId);
        }

        return $q;
    }

    public function scopePending($q)
    {
        return $q->whereNull('acknowledged_at');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->change_type] ?? $this->change_type;
    }
}
