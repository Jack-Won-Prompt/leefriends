<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'supply_product_id', 'product_name', 'unit',
        'qty', 'unit_price', 'line_amount', 'received_qty',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'integer',
        'line_amount' => 'integer',
        'received_qty' => 'integer',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplyProduct()
    {
        return $this->belongsTo(SupplyProduct::class);
    }
}
