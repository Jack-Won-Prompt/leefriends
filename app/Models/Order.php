<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_no', 'store_id', 'user_id', 'status', 'order_type',
        'store_amount', 'supply_amount', 'note', 'tax_invoice_id',
        'shipping_box_count', 'shipping_unit_price', 'shipping_fee',
        'statement_emailed_at', 'statement_email_count',
    ];

    protected $casts = [
        'store_amount' => 'integer',
        'supply_amount' => 'integer',
        'shipping_box_count' => 'integer',
        'shipping_unit_price' => 'integer',
        'shipping_fee' => 'integer',
        'statement_emailed_at' => 'datetime',
        'statement_email_count' => 'integer',
    ];

    /** 발주 합계 = 매장 출고가 합계 + 택배비 합계 */
    public function getOrderTotalAttribute(): int
    {
        return (int) $this->store_amount + (int) $this->shipping_fee;
    }

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

    /** 싯가 품목 단가가 아직 확정되지 않은 품목이 있는지 */
    public function hasPendingPrice(): bool
    {
        return $this->items()->where('price_pending', true)->exists();
    }

    /** 품목 라인합계로 주문 + 연결된 판매주문 금액을 재계산 (싯가 단가 확정 후 호출) */
    public function recomputeAmounts(): void
    {
        $items = $this->items()->get();
        $this->update([
            'store_amount' => (int) $items->sum('store_line_amount'),
            'supply_amount' => (int) $items->sum('supply_line_amount'),
        ]);

        foreach ($items->groupBy('sales_order_id') as $soId => $group) {
            if (! $soId) {
                continue;
            }
            SalesOrder::where('id', $soId)->update([
                'store_amount' => (int) $group->sum('store_line_amount'),
                'supply_amount' => (int) $group->sum('supply_line_amount'),
            ]);
        }
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
