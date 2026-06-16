<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'name', 'region', 'address', 'postcode', 'address_detail',
        'corp_postcode', 'corp_address', 'corp_address_detail',
        'phone', 'email', 'hours', 'lat', 'lng', 'image', 'is_active',
    ];

    /** 매장 포털 계정 (1개) */
    public function account()
    {
        return $this->hasOne(User::class)->where('role', 'store');
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
