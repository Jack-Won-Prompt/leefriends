<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxInvoice extends Model
{
    protected $fillable = [
        'invoice_no', 'supplier_id', 'supply_amount', 'vat',
        'total_amount', 'status', 'provider', 'nts_confirm_num',
        'popbill_mgt_key', 'issue_date', 'note',
    ];

    protected $casts = [
        'supply_amount' => 'integer',
        'vat' => 'integer',
        'total_amount' => 'integer',
        'issue_date' => 'date',
    ];

    public const STATUSES = [
        'issued' => '발행',
        'canceled' => '취소',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'tax_invoice_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
