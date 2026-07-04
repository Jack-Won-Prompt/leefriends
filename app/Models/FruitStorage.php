<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FruitStorage extends Model
{
    protected $fillable = [
        'name', 'temp_c', 'temp_f', 'ventilation', 'humidity',
        'dehumidification', 'storage_period', 'note',
        'is_shared', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_shared' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeShared($query)
    {
        return $query->where('is_shared', true);
    }
}
