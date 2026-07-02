<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * 본사 가용재고 부족으로 발주를 진행할 수 없을 때.
 */
class StockShortageException extends RuntimeException
{
    /** @param array<int, array{name:string, available:int, requested:int}> $shortages */
    public function __construct(public array $shortages)
    {
        parent::__construct('재고 부족');
    }

    public function messageLines(): array
    {
        return array_map(
            fn ($s) => "{$s['name']} — 가용 {$s['available']} / 요청 {$s['requested']}",
            $this->shortages
        );
    }

    public function summary(): string
    {
        return '본사 재고가 부족한 품목이 있습니다: '.implode(', ', $this->messageLines());
    }
}
