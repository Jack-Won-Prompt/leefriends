<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** 비밀번호 재설정 메일을 한국어 포털용 알림으로 발송 */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\PortalPasswordReset($token));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_admin',
        'role',
        'employment_type',
        'hourly_wage',
        'store_id',
        'supplier_id',
        'invite_token',
        'invited_at',
        'approval_status',
        'approved_at',
        'approved_by',
        'rejected_reason',
    ];

    /** 회원가입 승인 상태 */
    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    /** 자가 회원가입 시 선택 가능한 회원 종류 → 내부 역할 매핑 */
    public const SIGNUP_TYPES = [
        'store' => '제품 구매자',
        'supplier' => '제품 공급자',
    ];

    public const ROLES = [
        'hq' => '본사',
        'store' => '매장',
        'supplier' => '공급처',
    ];

    public const EMPLOYMENT_TYPES = [
        'regular' => '정직원',
        'part_time' => '아르바이트',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'hourly_wage' => 'integer',
        ];
    }

    /** 본사 승인이 완료되어 포털 이용이 가능한 계정인지 */
    public function isApproved(): bool
    {
        return $this->approval_status === self::APPROVAL_APPROVED;
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_status === self::APPROVAL_PENDING;
    }

    /** 승인 대기 중인 자가 가입 신청 목록 */
    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', self::APPROVAL_PENDING);
    }

    public function getSignupTypeLabelAttribute(): string
    {
        return self::SIGNUP_TYPES[$this->role] ?? ($this->role_label ?? $this->role);
    }

    public function isPartTime(): bool
    {
        return $this->employment_type === 'part_time';
    }

    public function getEmploymentLabelAttribute(): string
    {
        return self::EMPLOYMENT_TYPES[$this->employment_type] ?? '정직원';
    }

    /** 같은 소속(역할+조직)의 정직원들 */
    public function orgRegularStaff()
    {
        return User::where('role', $this->role)
            ->where('employment_type', 'regular')
            ->when($this->role === 'store', fn ($q) => $q->where('store_id', $this->store_id))
            ->when($this->role === 'supplier', fn ($q) => $q->where('supplier_id', $this->supplier_id))
            ->get();
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function notifications()
    {
        return $this->hasMany(AppNotification::class)->latest();
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }
}
