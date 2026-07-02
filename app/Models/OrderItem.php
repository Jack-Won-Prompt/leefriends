<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'sales_order_id', 'shipment_id', 'supply_product_id', 'supply_product_unit_id', 'product_name', 'unit',
        'supply_type', 'supplier_id', 'supplier_name', 'qty',
        'store_unit_price', 'supply_unit_price', 'store_line_amount',
        'supply_line_amount', 'price_pending', 'fulfillment_status', 'shipped_at', 'tax_invoice_id', 'supplier_statement_id',
    ];

    protected $casts = [
        'qty' => 'integer',
        'store_unit_price' => 'integer',
        'supply_unit_price' => 'integer',
        'store_line_amount' => 'integer',
        'supply_line_amount' => 'integer',
        'price_pending' => 'boolean',
        'shipped_at' => 'datetime',
    ];

    public const FULFILLMENT = [
        'pending' => '대기',
        'shipping' => '배송중',
        'delivered' => '배송완료',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function supplyProduct()
    {
        return $this->belongsTo(SupplyProduct::class, 'supply_product_id');
    }

    public function taxInvoice()
    {
        return $this->belongsTo(TaxInvoice::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function scopeForSupplier($q, int $supplierId)
    {
        return $q->where('supplier_id', $supplierId)->where('supply_type', 'supplier');
    }

    public function getFulfillmentLabelAttribute(): string
    {
        return self::FULFILLMENT[$this->fulfillment_status] ?? $this->fulfillment_status;
    }
}
