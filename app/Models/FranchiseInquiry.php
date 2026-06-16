<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FranchiseInquiry extends Model
{
    protected $fillable = [
        'name', 'phone', 'email', 'region', 'budget',
        'message', 'status', 'agree_privacy',
    ];

    protected $casts = [
        'agree_privacy' => 'boolean',
    ];

    public const STATUSES = [
        'new' => '신규',
        'contacted' => '상담중',
        'done' => '완료',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
