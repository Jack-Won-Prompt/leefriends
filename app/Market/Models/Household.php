<?php

namespace App\Market\Models;

use App\Market\Models\Concerns\BelongsToRegion;
use Illuminate\Database\Eloquent\Model;

class Household extends BaseModel
{
    use BelongsToRegion;

    protected $table = 'households';

    protected $fillable = ['region_code', 'base_ym', 'base_yq', 'housing_type', 'households'];

    protected function casts(): array
    {
        return ['households' => 'integer'];
    }
}
