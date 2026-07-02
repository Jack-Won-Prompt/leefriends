<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 본사 재고 — 실물(qty) / 예약(reserved_qty) / 가용(available).
 */
class HqInventory extends Model
{
    protected $fillable = ['supply_product_id', 'product_name', 'qty', 'reserved_qty'];

    protected $casts = [
        'qty' => 'integer',
        'reserved_qty' => 'integer',
    ];

    public function supplyProduct()
    {
        return $this->belongsTo(SupplyProduct::class);
    }

    public function getAvailableAttribute(): int
    {
        return (int) $this->qty - (int) $this->reserved_qty;
    }
}
