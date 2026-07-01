<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Services\Popbill\PopbillMessagingService;
use Illuminate\Support\Facades\Log;

/**
 * 발주 등록 시 매장에 입금요청 문자(계좌번호 + 발주금액)를 발송.
 */
class PaymentRequestSms
{
    public function __construct(private PopbillMessagingService $messaging)
    {
    }

    public function send(Order $order): void
    {
        $store = $order->store;
        $phone = preg_replace('/\D/', '', (string) ($store->phone ?? ''));
        $amount = (int) $order->order_total;

        if (! $phone || $amount <= 0) {
            return; // 수신번호 없거나 금액 0(샘플 등)이면 발송 안 함
        }

        $bank = config('popbill.deposit.bank');
        $account = config('popbill.deposit.account');
        $holder = config('popbill.deposit.holder');
        $corp = preg_replace('/\D/', '', (string) config('popbill.hq.corp_num'));

        $content = "[리프렌즈] 발주 접수\n"
            ."발주번호: {$order->order_no}\n"
            .'발주금액: '.number_format($amount)."원\n"
            ."입금계좌: {$bank} {$account}\n"
            ."예금주: {$holder}\n"
            .'확인 후 입금 부탁드립니다.';

        try {
            $this->messaging->send($corp, $phone, '발주 입금요청', $content, $store->name ?? null);
        } catch (\Throwable $e) {
            // 문자 실패가 발주 처리를 막지 않도록 로그만 남김
            Log::warning('입금요청 SMS 발송 실패', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
