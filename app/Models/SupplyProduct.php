<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyProduct extends Model
{
    protected $fillable = [
        'code', 'barcode', 'name', 'category', 'category_code', 'unit', 'spec', 'supply_type', 'supplier_id',
        'supply_price', 'store_price', 'tax_type', 'image', 'sort_order', 'is_active',
        'approval_status', 'registered_by', 'approval_note',
    ];

    /** 부가세 구분: inc(과세·부가세포함) / exc(과세·부가세별도) / exempt(면세) */
    public const TAX_TYPES = [
        'inc' => '과세 (부가세 포함)',
        'exc' => '과세 (부가세 별도)',
        'exempt' => '면세',
    ];

    /** 금액(라인합계)에서 tax_type 에 따라 공급가액/세액 산출 → [supply, tax] */
    public static function taxBreakdown(string $taxType, int $amount): array
    {
        return match ($taxType) {
            'exc' => [$amount, (int) round($amount * 0.1)],          // 별도: 금액=공급가, 세액 추가
            'exempt' => [$amount, 0],                                 // 면세
            default => [$s = (int) round($amount / 1.1), $amount - $s], // 포함: 금액=합계
        };
    }

    public function getTaxTypeLabelAttribute(): string
    {
        return self::TAX_TYPES[$this->tax_type] ?? $this->tax_type;
    }

    protected $casts = [
        'is_active' => 'boolean',
        'supply_price' => 'integer',
        'store_price' => 'integer',
        'sort_order' => 'integer',
    ];

    public const SUPPLY_TYPES = [
        'hq' => '본사 직공급',
        'supplier' => '공급처 직배송',
    ];

    /** 대분류명 → 대분류코드(코드 접두) 매핑 */
    public const CATEGORY_CODES = [
        '마카롱' => 'MAC',
        '쿠키' => 'COO',
        '재료' => 'MAT',
    ];

    public const CODE_PREFIX = 'P'; // 매핑에 없는 분류의 폴백 접두

    /** 승인 상태 */
    public const APPROVAL_LABELS = [
        'approved' => '승인',
        'pending' => '승인대기',
        'rejected' => '반려',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $product) {
            // 대분류명으로부터 대분류코드 자동 설정 (DB 카테고리 우선, 없으면 상수 폴백)
            if (empty($product->category_code)) {
                $product->category_code = ProductCategory::codeFor($product->category)
                    ?? (self::CATEGORY_CODES[$product->category] ?? null);
            }
            // 대분류별 연속 품목코드 자동 채번 (예: MAC001, COO002, MAT010)
            if (empty($product->code)) {
                $product->code = self::generateCode($product->category_code);
            }
        });
    }

    /** 대분류코드별 다음 연속 품목코드 (예: MAC → MAC001, MAC002 …) */
    public static function generateCode(?string $categoryCode = null): string
    {
        $prefix = $categoryCode ?: self::CODE_PREFIX;
        $last = self::where('code', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(code, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->value('code');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return sprintf('%s%03d', $prefix, $next);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function units()
    {
        return $this->hasMany(SupplyProductUnit::class)->orderByDesc('is_default')->orderBy('sort_order')->orderBy('id');
    }

    public function supplierPrices()
    {
        return $this->hasMany(SupplierUnitPrice::class);
    }

    /** 이 제품을 공급하는 공급처들 (기본 공급처 = supplier_id) */
    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'supplier_unit_prices')->distinct();
    }

    public function defaultSupplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function defaultUnit()
    {
        return $this->hasOne(SupplyProductUnit::class)->where('is_default', true);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** 매장 노출 가능: 활성 + 승인 완료 */
    public function scopeApproved($q)
    {
        return $q->where('approval_status', 'approved');
    }

    public function getApprovalLabelAttribute(): string
    {
        return self::APPROVAL_LABELS[$this->approval_status] ?? $this->approval_status;
    }

    /** 대분류 정렬순서(기준정보 카테고리 순서) → 품목 정렬 */
    public function scopeCatalogOrder($q)
    {
        $codes = ProductCategory::ordered()->pluck('code')->filter()->values()->all();
        if (empty($codes)) {
            $codes = array_values(self::CATEGORY_CODES);
        }
        $list = "'" . implode("','", $codes) . "'";

        return $q->orderByRaw("FIELD(category_code, $list)")->orderBy('sort_order')->orderBy('id');
    }

    public function getSupplyTypeLabelAttribute(): string
    {
        return self::SUPPLY_TYPES[$this->supply_type] ?? $this->supply_type;
    }

    public function getMarginAttribute(): int
    {
        return max(0, $this->store_price - $this->supply_price);
    }
}
