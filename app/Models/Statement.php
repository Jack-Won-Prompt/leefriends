<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 거래명세서 발송 이력 (스냅샷).
 */
class Statement extends Model
{
    protected $fillable = [
        'order_id', 'store_id', 'store_name', 'email', 'statement_date', 'item_count', 'total', 'items', 'sent_by', 'sent_at', 'resend_count', 'tax_invoice_id',
        'viewed_at', 'confirmed_at', 'confirmed_by',
    ];

    protected $casts = [
        'items' => 'array',
        'item_count' => 'integer',
        'total' => 'integer',
        'resend_count' => 'integer',
        'sent_at' => 'datetime',
        'statement_date' => 'date',
        'viewed_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    /** 매장 수취 상태: pending(미열람) / viewed(열람) / confirmed(확인) */
    public function receiptStatus(): string
    {
        if ($this->confirmed_at) {
            return 'confirmed';
        }

        return $this->viewed_at ? 'viewed' : 'pending';
    }

    public function receiptLabel(): string
    {
        return ['pending' => '미열람', 'viewed' => '열람됨', 'confirmed' => '확인됨'][$this->receiptStatus()];
    }

    /** 표시용 발행일자 (미지정 시 작성일) */
    public function issueDate(): \Illuminate\Support\Carbon
    {
        return $this->statement_date ?? $this->created_at;
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function taxInvoice()
    {
        return $this->belongsTo(TaxInvoice::class, 'tax_invoice_id');
    }

    /** PDF/메일 렌더용 매장 객체 (삭제된 매장이면 스냅샷으로 대체) */
    public function storeForRender(): Store
    {
        return $this->store ?? new Store(['name' => $this->store_name, 'email' => $this->email]);
    }
}
