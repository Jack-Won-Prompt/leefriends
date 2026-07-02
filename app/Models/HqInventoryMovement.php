<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 본사 재고 이동 이력.
 */
class HqInventoryMovement extends Model
{
    protected $fillable = [
        'supply_product_id', 'product_name', 'type',
        'qty_delta', 'reserved_delta', 'balance_qty', 'balance_reserved',
        'source', 'ref_type', 'ref_id', 'user_id', 'note',
    ];

    protected $casts = [
        'qty_delta' => 'integer',
        'reserved_delta' => 'integer',
        'balance_qty' => 'integer',
        'balance_reserved' => 'integer',
    ];

    public const TYPE_LABELS = [
        'inbound' => '입고',
        'reserve' => '출고예정(예약)',
        'release' => '예약해제',
        'ship' => '출고',
        'adjust' => '조정',
    ];

    public function supplyProduct()
    {
        return $this->belongsTo(SupplyProduct::class);
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }
}
