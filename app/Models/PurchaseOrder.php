<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'po_no', 'supplier_id', 'supplier_name', 'status', 'total_amount',
        'note', 'created_by', 'confirmed_at', 'received_at', 'statement_issued_at',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'confirmed_at' => 'datetime',
        'received_at' => 'datetime',
        'statement_issued_at' => 'datetime',
    ];

    public const STATUSES = [
        'ordered' => '발주',
        'confirmed' => '공급처 확인',
        'received' => '입고완료',
        'canceled' => '취소',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForSupplier($query, int $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }
}
