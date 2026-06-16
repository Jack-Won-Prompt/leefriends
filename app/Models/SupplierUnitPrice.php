<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierUnitPrice extends Model
{
    protected $fillable = [
        'supply_product_id', 'supplier_id', 'supply_product_unit_id', 'supply_price',
    ];

    protected $casts = [
        'supply_price' => 'integer',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function unit()
    {
        return $this->belongsTo(SupplyProductUnit::class, 'supply_product_unit_id');
    }
}
