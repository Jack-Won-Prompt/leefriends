<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 모바일 앱 FCM 기기 토큰 등록/해제.
 */
class DeviceTokenController extends Controller
{
    /**
     * POST /api/v1/device-tokens
     * body: { token, platform? }
     * 토큰은 unique — 기존 토큰이면 소유자/플랫폼/last_used_at 갱신.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'in:android,ios,web'],
        ]);

        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'] ?? null,
                'last_used_at' => now(),
            ],
        );

        return response()->json(['message' => '기기 토큰이 등록되었습니다.']);
    }

    /**
     * DELETE /api/v1/device-tokens
     * body: { token }  — 로그아웃/알림 해제 시 호출.
     */
    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
        ]);

        DeviceToken::where('token', $data['token'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => '기기 토큰이 해제되었습니다.']);
    }
}
