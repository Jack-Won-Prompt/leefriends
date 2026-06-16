<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'party_type', 'party_id', 'last_message_at', 'last_message', 'hq_unread', 'party_unread',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'hq_unread' => 'integer',
        'party_unread' => 'integer',
    ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /** 상대방(매장/공급처) 모델 */
    public function party()
    {
        return $this->party_type === 'supplier'
            ? Supplier::find($this->party_id)
            : Store::find($this->party_id);
    }

    public function getPartyNameAttribute(): string
    {
        return optional($this->party())->name ?? ($this->party_type === 'supplier' ? '공급처' : '매장');
    }

    public function getPartyLabelAttribute(): string
    {
        return $this->party_type === 'supplier' ? '공급처' : '매장';
    }

    /** 본사 ↔ (매장/공급처) 대화방을 찾거나 생성 */
    public static function findOrCreateFor(string $type, int $id): self
    {
        return static::firstOrCreate(
            ['party_type' => $type, 'party_id' => $id],
        );
    }

    /** 사용자(역할)에 해당하는 본인 대화방 (매장/공급처 계정용) */
    public static function forUser(User $user): ?self
    {
        if ($user->role === 'store' && $user->store_id) {
            return static::findOrCreateFor('store', (int) $user->store_id);
        }
        if ($user->role === 'supplier' && $user->supplier_id) {
            return static::findOrCreateFor('supplier', (int) $user->supplier_id);
        }

        return null;
    }

    /** 사용자가 이 대화방에 접근 가능한가 */
    public function accessibleBy(User $user): bool
    {
        if ($user->role === 'hq') {
            return true;
        }
        if ($user->role === 'store') {
            return $this->party_type === 'store' && (int) $this->party_id === (int) $user->store_id;
        }
        if ($user->role === 'supplier') {
            return $this->party_type === 'supplier' && (int) $this->party_id === (int) $user->supplier_id;
        }

        return false;
    }
}
