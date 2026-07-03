<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'shipment_no', 'seller_type', 'supplier_id', 'store_id', 'status',
        'carrier', 'tracking_no', 'item_count', 'total_qty', 'supply_amount',
        'note', 'confirmed_at', 'delivered_at', 'received_at', 'received_by',
    ];

    protected $casts = [
        'item_count' => 'integer',
        'total_qty' => 'integer',
        'supply_amount' => 'integer',
        'confirmed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public const STATUSES = [
        'created' => '작성',
        'confirmed' => '배송중',
        'delivered' => '배송완료',
        'received' => '입고완료',
        'canceled' => '취소',
    ];

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
