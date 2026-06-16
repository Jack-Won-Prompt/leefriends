<?php

namespace App\Services\TaxInvoice;

use App\Models\TaxInvoice;

/**
 * 세금계산서 발행 드라이버 인터페이스.
 *
 * 현재는 InternalIssuer(내부 발행)만 사용하며,
 * 추후 팝빌(Popbill) 전자세금계산서 연동 시 PopbillIssuer 로 교체합니다.
 * (config/services.php 의 tax_invoice.driver 로 전환)
 */
interface TaxInvoiceIssuer
{
    /**
     * 생성된 세금계산서를 실제 발행 처리한다.
     * 외부 연동 드라이버는 이 단계에서 API 호출 후
     * nts_confirm_num / popbill_mgt_key 를 채운다.
     */
    public function issue(TaxInvoice $invoice): TaxInvoice;
}
