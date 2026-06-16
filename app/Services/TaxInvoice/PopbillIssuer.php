<?php

namespace App\Services\TaxInvoice;

use App\Models\TaxInvoice;
use RuntimeException;

/**
 * 팝빌(Popbill) 전자세금계산서 연동 드라이버 (추후 연동 예정).
 *
 * 연동 시 TODO:
 *  1) composer require linkhub-corp/popbill-php (또는 REST API)
 *  2) config/services.php 에 popbill 링크아이디/시크릿/회사 사업자번호 설정
 *  3) 아래 issue() 에서 TaxinvoiceService->RegistIssue() 호출 후
 *     응답의 국세청승인번호(ntsConfirmNum)/문서관리번호(mgtKey)를 저장
 *  4) config/services.php 의 tax_invoice.driver 를 'popbill' 로 변경
 */
class PopbillIssuer implements TaxInvoiceIssuer
{
    public function issue(TaxInvoice $invoice): TaxInvoice
    {
        // 연동 전까지는 명시적으로 막아 둔다.
        throw new RuntimeException('팝빌 전자세금계산서 연동이 아직 설정되지 않았습니다. (config services.tax_invoice.driver)');

        // 연동 예시(주석):
        // $service = app('popbill.taxinvoice');
        // $result = $service->RegistIssue($corpNum, $mgtKey, $taxinvoice, ...);
        // $invoice->update([
        //     'provider' => 'popbill',
        //     'status' => 'issued',
        //     'nts_confirm_num' => $result->ntsConfirmNum,
        //     'popbill_mgt_key' => $mgtKey,
        // ]);
        // return $invoice;
    }
}
