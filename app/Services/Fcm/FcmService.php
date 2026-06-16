<?php

namespace App\Services\Fcm;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging (HTTP v1) 발송 서비스.
 *
 * 설정: config/services.php → fcm.credentials (서비스계정 JSON 경로)
 *   .env: FCM_CREDENTIALS=storage/app/firebase/leefriends-service-account.json
 *
 * 자격증명이 없으면 안전하게 no-op (인앱 알림은 별도 저장되므로 앱은 정상 동작).
 */
class FcmService
{
    public function isConfigured(): bool
    {
        return (bool) $this->credentialsPath() && is_readable($this->credentialsPath());
    }

    /**
     * 여러 디바이스 토큰으로 알림 전송.
     *
     * @param  array<string>  $tokens
     * @return int 성공 전송 수
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): int
    {
        $tokens = array_values(array_filter(array_unique($tokens)));
        if (empty($tokens)) {
            return 0;
        }

        if (! $this->isConfigured()) {
            Log::info('[FCM] 미설정 - 전송 건너뜀 (인앱 알림만 저장)', ['title' => $title, 'tokens' => count($tokens)]);

            return 0;
        }

        try {
            $accessToken = $this->accessToken();
            $projectId = $this->projectId();
        } catch (\Throwable $e) {
            Log::warning('[FCM] 토큰 발급 실패: ' . $e->getMessage());

            return 0;
        }

        // data 값은 문자열만 허용
        $data = array_map(fn ($v) => (string) $v, $data);

        $sent = 0;
        foreach ($tokens as $token) {
            $resp = Http::withToken($accessToken)
                ->acceptJson()
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data' => $data,
                        'android' => ['priority' => 'high'],
                    ],
                ]);

            if ($resp->successful()) {
                $sent++;
            } else {
                Log::warning('[FCM] 전송 실패', ['status' => $resp->status(), 'body' => $resp->body()]);
            }
        }

        return $sent;
    }

    private function accessToken(): string
    {
        return Cache::remember('fcm_access_token', 3000, function () {
            $cred = $this->credentials();
            $now = time();

            $jwt = $this->encodeJwt([
                'iss' => $cred['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $cred['token_uri'],
                'iat' => $now,
                'exp' => $now + 3600,
            ], $cred['private_key']);

            $resp = Http::asForm()->post($cred['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $resp->successful() || ! $resp->json('access_token')) {
                throw new \RuntimeException('access_token 발급 실패: ' . $resp->body());
            }

            return $resp->json('access_token');
        });
    }

    private function encodeJwt(array $claims, string $privateKey): string
    {
        $header = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $this->b64(json_encode($claims));
        $signingInput = "{$header}.{$payload}";

        $signature = '';
        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return "{$signingInput}." . $this->b64($signature);
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function credentials(): array
    {
        return json_decode(file_get_contents($this->credentialsPath()), true);
    }

    private function projectId(): string
    {
        return config('services.fcm.project_id') ?: ($this->credentials()['project_id'] ?? '');
    }

    private function credentialsPath(): ?string
    {
        $path = config('services.fcm.credentials');
        if (! $path) {
            return null;
        }

        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path)
            ? $path
            : base_path($path);
    }
}
