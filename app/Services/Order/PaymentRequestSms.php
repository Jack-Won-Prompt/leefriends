<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Store;
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

    /** 자동 발송(발주 등록 시) — 실패해도 예외를 삼키고 로그만 남긴다. */
    public function send(Order $order): void
    {
        try {
            $this->dispatch($order);
        } catch (\Throwable $e) {
            Log::warning('입금요청 SMS 발송 실패', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /** 수동 발송(입금요청 버튼) — 실패 시 예외를 던져 호출측이 결과를 표시하게 한다. 반환: 접수번호. */
    public function dispatch(Order $order): string
    {
        $store = $order->store;
        $phone = preg_replace('/\D/', '', (string) ($store->phone ?? ''));
        $amount = (int) $order->order_total;

        if (! $phone) {
            throw new \RuntimeException('매장 전화번호가 없습니다.');
        }
        if ($amount <= 0) {
            throw new \RuntimeException('발주금액이 0원입니다.');
        }

        $bank = config('popbill.deposit.bank');
        $account = config('popbill.deposit.account');
        $holder = config('popbill.deposit.holder');
        // 발신번호가 승인된 SMS 발송 사업자번호(발행 사업자번호와 분리)
        $corp = preg_replace('/\D/', '', (string) config('popbill.sms.corp_num'));

        $content = "[리프렌즈] 발주 접수\n"
            ."발주번호: {$order->order_no}\n"
            .'발주금액: '.number_format($amount)."원\n"
            ."입금계좌: {$bank} {$account}\n"
            ."예금주: {$holder}\n"
            .'확인 후 입금 부탁드립니다.';

        return $this->messaging->send($corp, $phone, '발주 입금요청', $content, $store->name ?? null);
    }

    /** 매장 미입금 총액 안내 문자 — 반환: 접수번호. 실패 시 예외. */
    public function dispatchUnpaidSummary(Store $store, int $unpaidAmount, int $unpaidCount): string
    {
        $phone = preg_replace('/\D/', '', (string) ($store->phone ?? ''));
        if (! $phone) {
            throw new \RuntimeException('매장 전화번호가 없습니다.');
        }
        if ($unpaidAmount <= 0) {
            throw new \RuntimeException('미입금 금액이 없습니다.');
        }

        $bank = config('popbill.deposit.bank');
        $account = config('popbill.deposit.account');
        $holder = config('popbill.deposit.holder');
        $corp = preg_replace('/\D/', '', (string) config('popbill.sms.corp_num'));

        $content = "[리프렌즈] 미입금 안내\n"
            .'미입금 '.number_format($unpaidCount).'건 · '.number_format($unpaidAmount)."원\n"
            ."입금계좌: {$bank} {$account}\n"
            ."예금주: {$holder}\n"
            .'확인 후 입금 부탁드립니다.';

        return $this->messaging->send($corp, $phone, '미입금 안내', $content, $store->name ?? null);
    }
}
