<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreLedgerEntry extends Model
{
    protected $fillable = [
        'store_id', 'type', 'amount', 'balance_after',
        'source', 'ref_type', 'ref_id', 'memo', 'created_by',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
    ];

    public const TYPES = [
        'charge' => '충전',
        'order' => '발주 차감',
        'refund' => '환불',
        'adjust' => '조정',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
