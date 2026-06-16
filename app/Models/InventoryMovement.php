<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    protected $fillable = [
        'store_id', 'supply_product_id', 'supply_product_unit_id',
        'product_name', 'unit_name', 'type', 'source', 'qty',
        'balance_after', 'shipment_id', 'user_id', 'note',
    ];

    protected $casts = [
        'qty' => 'integer',
        'balance_after' => 'integer',
    ];

    public const TYPES = [
        'in' => '입고',
        'out' => '출고',
        'adjust' => '조정',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
