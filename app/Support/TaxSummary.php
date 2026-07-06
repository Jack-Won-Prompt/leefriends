<?php

namespace App\Support;

use App\Models\SupplyProduct;

/**
 * 거래명세서 부가세 요약. 라인별 tax_type(inc/exc/exempt)으로
 * 과세 공급가액 / 부가세 / 면세 공급가액 / 합계를 산출한다.
 */
class TaxSummary
{
    /**
     * @param  iterable  $lines  각 항목 ['amount'=>int, 'tax_type'=>string]
     * @return array{taxable:int, vat:int, exempt:int, total:int}
     */
    public static function fromLines(iterable $lines): array
    {
        $taxable = 0;
        $vat = 0;
        $exempt = 0;

        foreach ($lines as $l) {
            $taxType = $l['tax_type'] ?? 'inc';
            $amount = (int) ($l['amount'] ?? 0);
            [$supply, $tax] = SupplyProduct::taxBreakdown($taxType, $amount);
            if ($taxType === 'exempt') {
                $exempt += $supply;
            } else {
                $taxable += $supply;
                $vat += $tax;
            }
        }

        return [
            'taxable' => (int) $taxable,
            'vat' => (int) $vat,
            'exempt' => (int) $exempt,
            'total' => (int) ($taxable + $vat + $exempt),
        ];
    }

    /** 발주(품목 + 택배비)로부터 요약 산출 — items.supplyProduct 필요 */
    public static function fromOrder($order): array
    {
        $lines = collect($order->items)->map(fn ($it) => [
            'amount' => (int) $it->store_line_amount,
            'tax_type' => $it->supplyProduct->tax_type ?? 'exc',
        ])->all();

        if ((int) $order->shipping_fee > 0) {
            $lines[] = ['amount' => (int) $order->shipping_fee, 'tax_type' => 'inc'];
        }

        return self::fromLines($lines);
    }
}
