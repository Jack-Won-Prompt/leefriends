<?php

namespace App\Market\Models;

use App\Market\Models\Concerns\BelongsToRegion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardSale extends BaseModel
{
    use BelongsToRegion;

    protected $fillable = [
        'region_code', 'base_ym', 'base_yq', 'industry_code', 'industry_name',
        'day_type', 'time_band', 'sales_amount', 'sales_count',
    ];

    protected function casts(): array
    {
        return [
            'sales_amount' => 'integer',
            'sales_count' => 'integer',
        ];
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_code', 'code');
    }
}
