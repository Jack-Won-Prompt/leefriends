<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const PORTAL_ROLES = ['hq', 'store', 'supplier'];

    /**
     * POST /api/v1/auth/login
     * 매장(및 포털) 계정 로그인 → Sanctum 토큰 발급.
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => '이메일 또는 비밀번호가 올바르지 않습니다.',
            ]);
        }

        $role = $this->portalRole($user);
        if (! $role) {
            throw ValidationException::withMessages([
                'email' => '포털 사용 권한이 없는 계정입니다.',
            ]);
        }

        $device = $data['device_name'] ?? 'mobile';
        $token = $user->createToken($device, ['portal'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user, $role),
        ]);
    }

    /**
     * GET /api/v1/me
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $this->userPayload($user, $this->portalRole($user)),
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => '로그아웃되었습니다.']);
    }

    private function userPayload(User $user, ?string $role): array
    {
        $user->loadMissing('store');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role,
            'role_label' => User::ROLES[$role] ?? $role,
            'store_id' => $user->store_id,
            'store_name' => $user->store?->name,
            'employment_type' => $user->employment_type ?? 'regular',
            'is_part_time' => method_exists($user, 'isPartTime') ? $user->isPartTime() : false,
            'hourly_wage' => (int) ($user->hourly_wage ?? 0),
        ];
    }

    private function portalRole(User $user): ?string
    {
        $role = $user->role ?: ($user->is_admin ? 'hq' : null);

        return in_array($role, self::PORTAL_ROLES, true) ? $role : null;
    }
}
