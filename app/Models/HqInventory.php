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

    /** 가용 = 실물 − 예약. 0 미만으로 내려가지 않음(예약이 실물보다 많아도 0 — 예: 재고 없음 처리 후 기존 발주 예약분) */
    public function getAvailableAttribute(): int
    {
        return max(0, (int) $this->qty - (int) $this->reserved_qty);
    }
}
