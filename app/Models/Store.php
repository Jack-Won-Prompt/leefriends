<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'name', 'region', 'address', 'postcode', 'address_detail',
        'corp_postcode', 'corp_address', 'corp_address_detail',
        'biz_no', 'ceo', 'biz_type', 'biz_class',
        'phone', 'email', 'hours', 'lat', 'lng', 'image', 'is_active',
        'settlement_type', 'virtual_account', 'ledger_balance',
    ];

    public const SETTLEMENT_TYPES = [
        'postpaid' => '후불',
        'prepaid' => '선입금(예치금)',
    ];

    public function getSettlementLabelAttribute(): string
    {
        return self::SETTLEMENT_TYPES[$this->settlement_type] ?? '후불';
    }

    /** 매장 포털 계정 (1개) */
    public function account()
    {
        return $this->hasOne(User::class)->where('role', 'store');
    }

    /** 거래 원장 */
    public function ledgerEntries()
    {
        return $this->hasMany(StoreLedgerEntry::class)->latest();
    }

    /** 배송 주소 전체 (우편번호 + 주소 + 상세) */
    public function getFullDeliveryAddressAttribute(): string
    {
        return trim(($this->address ?? '') . ' ' . ($this->address_detail ?? ''));
    }

    public function getFullCorpAddressAttribute(): string
    {
        return trim(($this->corp_address ?? '') . ' ' . ($this->corp_address_detail ?? ''));
    }

    protected $casts = [
        'is_active' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
        'ledger_balance' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
