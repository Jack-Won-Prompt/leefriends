<?php

namespace App\Services\Popbill;

use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillTaxinvoice;
use Linkhub\Popbill\Taxinvoice;
use Linkhub\Popbill\TaxinvoiceDetail;

/**
 * 팝빌 전자세금계산서 SDK 래퍼 (ce-admin 구현 참조).
 * config/popbill.php 의 LinkID/SecretKey/IsTest 사용.
 */
class PopbillTaxinvoiceService
{
    private PopbillTaxinvoice $api;

    public function __construct()
    {
        if (! defined('LINKHUB_COMM_MODE')) {
            define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE', 'CURL'));
        }
        $this->api = new PopbillTaxinvoice(config('popbill.LinkID'), config('popbill.SecretKey'));
        $this->api->IsTest((bool) config('popbill.IsTest', true));
        $this->api->IPRestrictOnOff((bool) config('popbill.IPRestrictOnOff', true));
        $this->api->UseStaticIP((bool) config('popbill.UseStaticIP', false));
        $this->api->UseLocalTimeYN((bool) config('popbill.UseLocalTimeYN', true));
    }

    public function newInvoice(): Taxinvoice
    {
        return new Taxinvoice();
    }

    public function newDetail(): TaxinvoiceDetail
    {
        return new TaxinvoiceDetail();
    }

    /** 잔여 포인트 (연동 점검용) */
    public function getBalance(string $corpNum): float
    {
        try {
            return $this->api->GetBalance($corpNum);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 즉시발행 (등록+발행). 반환: ->code / ->ntsConfirmNum 등 */
    public function registIssue(string $corpNum, Taxinvoice $invoice, ?string $userId = null, bool $forceIssue = false, ?string $memo = null): object
    {
        try {
            return $this->api->RegistIssue($corpNum, $invoice, $userId, false, $forceIssue, $memo);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 발행 문서 상태/상세 (발행자=SELL) */
    public function getInfo(string $corpNum, string $mgtKey): object
    {
        try {
            return $this->api->GetInfo($corpNum, 'SELL', $mgtKey);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 발행취소 */
    public function cancelIssue(string $corpNum, string $mgtKey, ?string $memo = null, ?string $userId = null): object
    {
        try {
            return $this->api->CancelIssue($corpNum, 'SELL', $mgtKey, $memo, $userId);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 팝빌 문서함 팝업 URL */
    public function getPopUpUrl(string $corpNum, string $mgtKey, ?string $userId = null): string
    {
        try {
            return $this->api->GetPopUpURL($corpNum, 'SELL', $mgtKey, $userId);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    private function fail(PopbillException $e): never
    {
        throw new \RuntimeException('[Popbill '.$e->getCode().'] '.$e->getMessage(), (int) $e->getCode(), $e);
    }
}
