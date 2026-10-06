<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 범용 key-value 설정 (관리자가 수정하는 안내 문구 등).
 */
class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /** 주문 불가 안내 메시지 설정 키 */
    public const ORDER_BLOCK_MESSAGE = 'order_block_message';

    public const DEFAULTS = [
        self::ORDER_BLOCK_MESSAGE => '기존의 발주 미정산금이 남아 있어서, 발주등록을 할 수 없습니다. 미정산금을 입금 처리하시면 이후 발주 등록이 가능합니다.',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        try {
            $row = static::where('key', $key)->first();
            if ($row && $row->value !== null && $row->value !== '') {
                return $row->value;
            }
        } catch (\Throwable $e) {
            // 마이그레이션 전 등 — 기본값 폴백
        }

        return $default ?? self::DEFAULTS[$key] ?? null;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function orderBlockMessage(): string
    {
        return self::get(self::ORDER_BLOCK_MESSAGE) ?? self::DEFAULTS[self::ORDER_BLOCK_MESSAGE];
    }
}
