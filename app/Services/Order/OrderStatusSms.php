<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Models\Shipment;
use App\Models\Store;
use App\Services\Popbill\PopbillMessagingService;
use Illuminate\Support\Facades\Log;

/**
 * 발주/출고 상태 변경 시 매장에 안내 SMS (출고/배송완료/취소).
 * 실패해도 처리 흐름을 막지 않도록 예외를 삼키고 로그만 남긴다.
 */
class OrderStatusSms
{
    public function __construct(private PopbillMessagingService $messaging)
    {
    }

    /** 출고 확정 → 출고 안내 (송장 포함) */
    public function shipped(Shipment $shipment): void
    {
        $carrier = $shipment->carrier ?: '택배';
        $tracking = $shipment->tracking_no;
        $body = "[리프렌즈] 출고 안내\n"
            ."발주 상품이 출고되었습니다.\n"
            ."택배사: {$carrier}\n"
            .($tracking ? "송장번호: {$tracking}\n" : "배송방식: 직접배송\n")
            .'상품 도착까지 잠시만 기다려 주세요.';
        $this->toStore($shipment->store, '출고 안내', $body);
    }

    /** 배송완료 → 완료 안내 */
    public function delivered(Shipment $shipment): void
    {
        $body = "[리프렌즈] 배송완료\n발주 상품이 배송완료되었습니다. 수령 및 검수 부탁드립니다.";
        $this->toStore($shipment->store, '배송완료 안내', $body);
    }

    /** 발주 취소 → 취소 안내 */
    public function canceled(Order $order): void
    {
        $body = "[리프렌즈] 발주 취소\n발주번호 {$order->order_no} 발주가 취소되었습니다.";
        $this->toStore($order->store, '발주 취소 안내', $body);
    }

    private function toStore(?Store $store, string $subject, string $content): void
    {
        $phone = preg_replace('/\D/', '', (string) ($store->phone ?? ''));
        if (! $phone) {
            return;
        }
        $corp = preg_replace('/\D/', '', (string) config('popbill.sms.corp_num'));

        defer(function () use ($corp, $phone, $subject, $content, $store) {
            try {
                $this->messaging->send($corp, $phone, $subject, $content, $store->name ?? null);
            } catch (\Throwable $e) {
                Log::warning('상태변경 SMS 실패', ['subject' => $subject, 'store' => $store->id ?? null, 'error' => $e->getMessage()]);
            }
        });
    }
}
