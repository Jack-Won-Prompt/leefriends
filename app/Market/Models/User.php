<?php

namespace App\Market\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * MarketScope 회원. market DB 의 users 테이블을 사용한다.
 * leefriends 관리자는 별도 계정 체계이므로, 관리자 화면에서 만드는 분석·즐겨찾기는
 * marketOwner()(대표 계정)에 귀속시킨다.
 */
class User extends Authenticatable
{
    use Notifiable;

    protected $connection = 'market';

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'company', 'role',
        'marketing_agreed_at', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'marketing_agreed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(Analysis::class)->latest();
    }

    public function favoriteRegions(): HasMany
    {
        return $this->hasMany(FavoriteRegion::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** 관리자 화면에서 소유자로 사용할 market 대표 계정. */
    public static function marketOwner(): self
    {
        return static::query()->orderBy('id')->firstOrFail();
    }
}
