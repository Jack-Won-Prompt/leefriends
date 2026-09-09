<?php

namespace App\Market\Models;

use App\Market\Models\Concerns\BelongsToRegion;
use Illuminate\Database\Eloquent\Model;

class Student extends BaseModel
{
    use BelongsToRegion;

    protected $fillable = ['region_code', 'base_ym', 'base_yq', 'school_type', 'student_count'];

    protected function casts(): array
    {
        return ['student_count' => 'integer'];
    }
}
