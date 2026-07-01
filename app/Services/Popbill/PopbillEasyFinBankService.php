<?php

namespace App\Services\Popbill;

use Linkhub\Popbill\PopbillEasyFinBank;
use Linkhub\Popbill\PopbillException;

/**
 * 팝빌 계좌조회(EasyFinBank) SDK 래퍼.
 * 등록된 계좌의 거래내역(입출금)을 수집(Job)·조회한다. 계좌 등록은 팝빌 콘솔에서 수행.
 */
class PopbillEasyFinBankService
{
    private PopbillEasyFinBank $api;

    public function __construct()
    {
        if (! defined('LINKHUB_COMM_MODE')) {
            define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE', 'CURL'));
        }
        $this->api = new PopbillEasyFinBank(config('popbill.LinkID'), config('popbill.SecretKey'));
        $this->api->IsTest((bool) config('popbill.IsTest', true));
        $this->api->IPRestrictOnOff((bool) config('popbill.IPRestrictOnOff', true));
        $this->api->UseStaticIP((bool) config('popbill.UseStaticIP', false));
        $this->api->UseLocalTimeYN((bool) config('popbill.UseLocalTimeYN', true));
    }

    /** 등록된 계좌 목록 */
    public function listBankAccount(string $corpNum): array
    {
        try {
            return $this->api->ListBankAccount($corpNum);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 수집 요청 → jobID(18자) */
    public function requestJob(string $corpNum, string $bankCode, string $accountNumber, string $sDate, string $eDate): string
    {
        try {
            return $this->api->RequestJob($corpNum, $bankCode, $accountNumber, $sDate, $eDate);
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

    /** 거래내역 조회. $tradeType: []=전체, ['I']=입금, ['O']=출금 */
    public function search(string $corpNum, string $jobId, array $tradeType = [], ?string $searchString = null, int $page = 1, int $perPage = 100, string $order = 'D'): object
    {
        try {
            return $this->api->Search($corpNum, $jobId, $tradeType, $searchString, $page, $perPage, $order);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 거래 메모 저장 */
    public function saveMemo(string $corpNum, string $tid, string $memo): object
    {
        try {
            return $this->api->SaveMemo($corpNum, $tid, $memo);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 정액제 상태 (없으면 null) */
    public function getFlatRateState(string $corpNum, string $bankCode, string $accountNumber): ?object
    {
        try {
            return $this->api->GetFlatRateState($corpNum, $bankCode, $accountNumber);
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

    private function fail(PopbillException $e): never
    {
        throw new \RuntimeException('[Popbill '.$e->getCode().'] '.$e->getMessage(), (int) $e->getCode(), $e);
    }
}
