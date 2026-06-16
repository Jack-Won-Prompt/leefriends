<?php

namespace App\Services\TaxInvoice;

use App\Models\TaxInvoice;

/**
 * 내부 발행 드라이버 (현재 기본값).
 * 외부 전송 없이 집계·발행 상태만 기록한다.
 */
class InternalIssuer implements TaxInvoiceIssuer
{
    public function issue(TaxInvoice $invoice): TaxInvoice
    {
        $invoice->update([
            'provider' => 'internal',
            'status' => 'issued',
        ]);

        return $invoice;
    }
}
