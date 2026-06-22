<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 거래명세서 발송 이력 (스냅샷).
 */
class Statement extends Model
{
    protected $fillable = [
        'store_id', 'store_name', 'email', 'item_count', 'total', 'items', 'sent_by', 'sent_at', 'resend_count',
    ];

    protected $casts = [
        'items' => 'array',
        'item_count' => 'integer',
        'total' => 'integer',
        'resend_count' => 'integer',
        'sent_at' => 'datetime',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /** PDF/메일 렌더용 매장 객체 (삭제된 매장이면 스냅샷으로 대체) */
    public function storeForRender(): Store
    {
        return $this->store ?? new Store(['name' => $this->store_name, 'email' => $this->email]);
    }
}
