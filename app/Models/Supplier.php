<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name', 'biz_no', 'ceo', 'phone', 'email',
        'address', 'postcode', 'address_detail',
        'return_postcode', 'return_address', 'return_address_detail',
        'is_active',
    ];

    /** 법인 주소 전체 */
    public function getFullCorpAddressAttribute(): string
    {
        return trim(($this->address ?? '') . ' ' . ($this->address_detail ?? ''));
    }

    /** 반품 주소 전체 (없으면 법인 주소 사용) */
    public function getFullReturnAddressAttribute(): string
    {
        $base = $this->return_address ?: $this->address;
        $detail = $this->return_address ? $this->return_address_detail : $this->address_detail;

        return trim(($base ?? '') . ' ' . ($detail ?? ''));
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products()
    {
        return $this->hasMany(SupplyProduct::class);
    }

    /** 공급처 포털 계정 (1개) */
    public function account()
    {
        return $this->hasOne(User::class)->where('role', 'supplier');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function taxInvoices()
    {
        return $this->hasMany(TaxInvoice::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
