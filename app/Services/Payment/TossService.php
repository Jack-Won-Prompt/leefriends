<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;

/**
 * 토스페이먼츠 연동 (결제위젯 v2).
 * 결제 승인(confirm)·취소(cancel)·조회를 담당한다. 키는 config('services.toss').
 */
class TossService
{
    private array $cfg;

    public function __construct()
    {
        $this->cfg = config('services.toss');
    }

    public function clientKey(): ?string
    {
        return $this->cfg['client_key'] ?? null;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->cfg['client_key']) && ! empty($this->cfg['secret_key']);
    }

    public function isTestMode(): bool
    {
        return (bool) ($this->cfg['test_mode'] ?? true);
    }

    /** Basic 인증 헤더 = base64(secretKey + ":") */
    private function authHeader(): string
    {
        return 'Basic '.base64_encode(($this->cfg['secret_key'] ?? '').':');
    }

    /**
     * 결제 승인. 성공 시 토스 결제객체(array) 반환, 실패 시 TossException.
     * 가상계좌는 status=WAITING_FOR_DEPOSIT 로 돌아온다.
     */
    public function confirm(string $paymentKey, string $tossOrderId, int $amount): array
    {
        $res = Http::withHeaders([
            'Authorization' => $this->authHeader(),
            'Content-Type' => 'application/json',
        ])->timeout(20)->post($this->cfg['api_base'].'/v1/payments/confirm', [
            'paymentKey' => $paymentKey,
            'orderId' => $tossOrderId,
            'amount' => $amount,
        ]);

        $body = $res->json() ?? [];
        if (! $res->successful()) {
            throw new TossException($body['message'] ?? '결제 승인에 실패했습니다.', $body['code'] ?? null, $body);
        }

        return $body;
    }

    /** 결제 취소(환불). */
    public function cancel(string $paymentKey, string $reason, ?int $amount = null): array
    {
        $payload = ['cancelReason' => $reason];
        if ($amount !== null) {
            $payload['cancelAmount'] = $amount;
        }

        $res = Http::withHeaders([
            'Authorization' => $this->authHeader(),
            'Content-Type' => 'application/json',
        ])->timeout(20)->post($this->cfg['api_base']."/v1/payments/{$paymentKey}/cancel", $payload);

        $body = $res->json() ?? [];
        if (! $res->successful()) {
            throw new TossException($body['message'] ?? '결제 취소에 실패했습니다.', $body['code'] ?? null, $body);
        }

        return $body;
    }
}
