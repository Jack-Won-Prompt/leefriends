<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 공급처 거래명세서 (공급처 → 본사, 공급가 기준). 작성→저장→선택 발행.
 */
class SupplierStatement extends Model
{
    protected $fillable = [
        'supplier_id', 'supplier_name', 'statement_no', 'item_count',
        'supply_total', 'vat', 'total', 'items', 'tax_invoice_id', 'created_by',
    ];

    protected $casts = [
        'items' => 'array',
        'item_count' => 'integer',
        'supply_total' => 'integer',
        'vat' => 'integer',
        'total' => 'integer',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function taxInvoice()
    {
        return $this->belongsTo(TaxInvoice::class, 'tax_invoice_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'supplier_statement_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
