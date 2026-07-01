<?php

namespace App\Services\Popbill;

use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillHTTaxinvoice;

/**
 * 팝빌 홈택스 전자세금계산서 조회 SDK 래퍼.
 * 국세청 홈택스에 등록된 본사의 매출/매입 세금계산서를 수집(Job)·조회한다.
 * config/popbill.php 의 LinkID/SecretKey/IsTest 사용.
 */
class PopbillHometaxTaxinvoiceService
{
    private PopbillHTTaxinvoice $api;

    public function __construct()
    {
        if (! defined('LINKHUB_COMM_MODE')) {
            define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE', 'CURL'));
        }
        $this->api = new PopbillHTTaxinvoice(config('popbill.LinkID'), config('popbill.SecretKey'));
        $this->api->IsTest((bool) config('popbill.IsTest', true));
        $this->api->IPRestrictOnOff((bool) config('popbill.IPRestrictOnOff', true));
        $this->api->UseStaticIP((bool) config('popbill.UseStaticIP', false));
        $this->api->UseLocalTimeYN((bool) config('popbill.UseLocalTimeYN', true));
    }

    /** 수집 요청 → jobID(18자) 반환. $tiType: SELL|BUY, $dType: W|I|S */
    public function requestJob(string $corpNum, string $tiType, string $sDate, string $eDate, string $dType = 'W'): string
    {
        try {
            return $this->api->RequestJob($corpNum, $tiType, $dType, $sDate, $eDate);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 수집 상태 (jobState 1:대기 2:진행 3:완료) */
    public function getJobState(string $corpNum, string $jobId): object
    {
        try {
            return $this->api->GetJobState($corpNum, $jobId);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 수집 상태 목록 */
    public function listActiveJob(string $corpNum): array
    {
        try {
            return $this->api->ListActiveJob($corpNum);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 수집 결과 목록 조회 */
    public function search(string $corpNum, string $jobId, array $type = [], array $taxType = [], array $purposeType = [], int $page = 1, int $perPage = 20, string $order = 'D'): object
    {
        try {
            return $this->api->Search($corpNum, $jobId, $type, $taxType, $purposeType, null, null, null, $page, $perPage, $order);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 수집 결과 요약(건수/공급가/세액/합계) */
    public function summary(string $corpNum, string $jobId, array $type = [], array $taxType = [], array $purposeType = []): object
    {
        try {
            return $this->api->Summary($corpNum, $jobId, $type, $taxType, $purposeType);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 세금계산서 1건 상세 (국세청승인번호 기준) */
    public function getTaxinvoice(string $corpNum, string $ntsConfirmNum): object
    {
        try {
            return $this->api->GetTaxinvoice($corpNum, $ntsConfirmNum);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 홈택스 수집 공동인증서 만료일 (없으면 예외) */
    public function getCertificateExpireDate(string $corpNum): ?string
    {
        try {
            return $this->api->GetCertificateExpireDate($corpNum);
        } catch (PopbillException $e) {
            return null;
        }
    }

    /** 공동인증서 등록 팝업 URL */
    public function getCertificatePopUpURL(string $corpNum): string
    {
        try {
            return $this->api->GetCertificatePopUpURL($corpNum);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 정액제 상태 (없으면 null) */
    public function getFlatRateState(string $corpNum): ?object
    {
        try {
            return $this->api->GetFlatRateState($corpNum);
        } catch (PopbillException $e) {
            return null;
        }
    }

    /** 정액제 신청 팝업 URL */
    public function getFlatRatePopUpURL(string $corpNum): string
    {
        try {
            return $this->api->GetFlatRatePopUpURL($corpNum);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 과금정보 조회 */
    public function getChargeInfo(string $corpNum): object
    {
        try {
            return $this->api->GetChargeInfo($corpNum);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    private function fail(PopbillException $e): never
    {
        throw new \RuntimeException('[Popbill '.$e->getCode().'] '.$e->getMessage(), (int) $e->getCode(), $e);
    }
}
