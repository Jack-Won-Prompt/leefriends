<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxInvoice extends Model
{
    protected $fillable = [
        'invoice_no', 'direction', 'supplier_id', 'store_id', 'order_id',
        'invoicer_corp_num', 'invoicer_corp_name', 'invoicee_corp_num', 'invoicee_corp_name', 'invoicee_email',
        'line_items', 'issued_by', 'supply_amount', 'vat',
        'total_amount', 'status', 'provider', 'nts_confirm_num',
        'popbill_mgt_key', 'issue_date', 'note',
    ];

    protected $casts = [
        'supply_amount' => 'integer',
        'vat' => 'integer',
        'total_amount' => 'integer',
        'line_items' => 'array',
        'issue_date' => 'date',
    ];

    public const STATUSES = [
        'issued' => '발행',
        'canceled' => '취소',
    ];

    public const DIRECTIONS = [
        'supplier_to_hq' => '공급처 → 본사',
        'hq_to_store' => '본사 → 매장',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function getDirectionLabelAttribute(): string
    {
        return self::DIRECTIONS[$this->direction] ?? $this->direction;
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
