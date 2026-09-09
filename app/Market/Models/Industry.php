<?php

namespace App\Market\Models;

use Illuminate\Database\Eloquent\Model;

class Industry extends BaseModel
{
    protected $table = 'industries';

    protected $fillable = ['code', 'name', 'group_name', 'sort_order'];
}
