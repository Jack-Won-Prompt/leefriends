<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_no', 'store_id', 'user_id', 'status', 'order_type',
        'store_amount', 'supply_amount', 'note', 'tax_invoice_id',
    ];

    protected $casts = [
        'store_amount' => 'integer',
        'supply_amount' => 'integer',
    ];

    public function isSample(): bool
    {
        return $this->order_type === 'sample';
    }

    public const STATUSES = [
        'pending' => '접수',
        'processing' => '처리중',
        'shipping' => '배송중',
        'completed' => '완료',
        'canceled' => '취소',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }

    /** 본사→매장 세금계산서 (발행된 경우) */
    public function taxInvoice()
    {
        return $this->belongsTo(TaxInvoice::class, 'tax_invoice_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** 품목 배송상태를 종합해 주문 전체 상태를 재계산 */
    public function syncStatus(): void
    {
        if ($this->status === 'canceled') {
            return;
        }
        $items = $this->items()->get();
        if ($items->isEmpty()) {
            return;
        }
        $delivered = $items->where('fulfillment_status', 'delivered')->count();
        $shipping = $items->where('fulfillment_status', 'shipping')->count();

        if ($delivered === $items->count()) {
            $status = 'completed';
        } elseif ($delivered > 0 || $shipping > 0) {
            $status = 'shipping';
        } else {
            $status = 'pending';
        }
        if ($status !== $this->status) {
            $this->update(['status' => $status]);
        }
    }
}
