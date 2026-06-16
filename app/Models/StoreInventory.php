<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreInventory extends Model
{
    protected $fillable = [
        'store_id', 'supply_product_id', 'supply_product_unit_id',
        'product_name', 'unit_name', 'qty',
    ];

    protected $casts = [
        'qty' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(SupplyProduct::class, 'supply_product_id');
    }
}
