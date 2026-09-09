<?php

namespace App\Market\Models;

use App\Market\Models\Concerns\BelongsToRegion;
use Illuminate\Database\Eloquent\Model;

class CardSaleDemographic extends BaseModel
{
    use BelongsToRegion;

    protected $table = 'card_sales_demographics';

    protected $fillable = [
        'region_code', 'base_ym', 'base_yq', 'industry_code', 'gender', 'age_band', 'sales_amount', 'sales_count',
    ];

    protected function casts(): array
    {
        return [
            'sales_amount' => 'integer',
            'sales_count' => 'integer',
        ];
    }
}
