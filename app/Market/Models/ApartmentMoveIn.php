<?php

namespace App\Market\Models;

use App\Market\Models\Concerns\BelongsToRegion;
use Illuminate\Database\Eloquent\Model;

class ApartmentMoveIn extends BaseModel
{
    use BelongsToRegion;

    protected $fillable = ['region_code', 'complex_name', 'households', 'move_in_ym'];

    protected function casts(): array
    {
        return ['households' => 'integer'];
    }
}
