<?php

namespace App\Market\Models;

use App\Market\Models\Concerns\BelongsToRegion;
use Illuminate\Database\Eloquent\Model;

class ResidentPopulation extends BaseModel
{
    use BelongsToRegion;

    protected $fillable = ['region_code', 'base_ym', 'base_yq', 'gender', 'age_band', 'population'];

    protected function casts(): array
    {
        return ['population' => 'integer'];
    }
}
