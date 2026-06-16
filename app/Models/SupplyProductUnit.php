<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyProductUnit extends Model
{
    protected $fillable = [
        'supply_product_id', 'name', 'supply_price', 'store_price', 'is_default', 'sort_order',
    ];

    protected $casts = [
        'supply_price' => 'integer',
        'store_price' => 'integer',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(SupplyProduct::class, 'supply_product_id');
    }
}
