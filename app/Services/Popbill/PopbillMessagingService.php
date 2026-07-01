<?php

namespace App\Services\Popbill;

use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillMessaging;
use Illuminate\Support\Facades\Log;

/**
 * 팝빌 문자(SMS/LMS) 발송 래퍼.
 * 내용 길이에 따라 SMS/LMS 자동 선택(SendXMS). config('popbill.sms.simulate')=true면 실제 발송 대신 로그만.
 */
class PopbillMessagingService
{
    private PopbillMessaging $api;

    public function __construct()
    {
        if (! defined('LINKHUB_COMM_MODE')) {
            define('LINKHUB_COMM_MODE', config('popbill.LINKHUB_COMM_MODE', 'CURL'));
        }
        $this->api = new PopbillMessaging(config('popbill.LinkID'), config('popbill.SecretKey'));
        $this->api->IsTest((bool) config('popbill.IsTest', true));
        $this->api->IPRestrictOnOff((bool) config('popbill.IPRestrictOnOff', true));
        $this->api->UseStaticIP((bool) config('popbill.UseStaticIP', false));
        $this->api->UseLocalTimeYN((bool) config('popbill.UseLocalTimeYN', true));
    }

    /**
     * 단건 문자 발송. 반환: 접수번호(receiptNum) 또는 시뮬레이션 시 'SIMULATED'.
     */
    public function send(string $corpNum, string $to, string $subject, string $content, ?string $toName = null): string
    {
        $to = preg_replace('/\D/', '', $to);
        if ($to === '') {
            throw new \RuntimeException('수신번호가 없습니다.');
        }

        $sender = config('popbill.sms.sender');

        if ((bool) config('popbill.sms.simulate', false)) {
            Log::info('[SMS simulate]', compact('to', 'subject', 'content', 'sender'));

            return 'SIMULATED';
        }

        $messages = [(object) ['rcv' => $to, 'rcvnm' => $toName ?? '']];

        try {
            // SendXMS: 내용 길이에 따라 SMS/LMS 자동 선택
            return $this->api->SendXMS($corpNum, $sender, $subject, $content, $messages);
        } catch (PopbillException $e) {
            throw new \RuntimeException('[Popbill '.$e->getCode().'] '.$e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
