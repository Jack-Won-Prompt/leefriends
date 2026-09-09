<?php

namespace App\Market\Models;

use App\Market\Models\Concerns\BelongsToRegion;
use Illuminate\Database\Eloquent\Model;

class Academy extends BaseModel
{
    use BelongsToRegion;

    protected $table = 'academies';

    protected $fillable = ['region_code', 'base_ym', 'base_yq', 'category', 'industry_name', 'academy_count'];

    protected function casts(): array
    {
        return ['academy_count' => 'integer'];
    }
}
