<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $fillable = [
        'sales_order_no', 'order_id', 'store_id', 'seller_type', 'supplier_id',
        'status', 'order_type', 'item_count', 'store_amount', 'supply_amount', 'confirmed_at',
    ];

    protected $casts = [
        'item_count' => 'integer',
        'store_amount' => 'integer',
        'supply_amount' => 'integer',
        'confirmed_at' => 'datetime',
    ];

    public const STATUSES = [
        'created' => '접수',
        'confirmed' => '확인',
        'shipped' => '배송시작',
        'received' => '입고완료',
        'canceled' => '취소',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getSellerNameAttribute(): string
    {
        return $this->seller_type === 'supplier' ? (optional($this->supplier)->name ?? '공급처') : '본사';
    }

    public function scopeForSeller($q, string $sellerType, ?int $supplierId = null)
    {
        $q->where('seller_type', $sellerType);
        if ($sellerType === 'supplier') {
            $q->where('supplier_id', $supplierId);
        }

        return $q;
    }
}
