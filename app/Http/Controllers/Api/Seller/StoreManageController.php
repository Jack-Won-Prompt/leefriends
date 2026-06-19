<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Mail\PortalInvitation;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * 매장 관리 — 본사 전용 (목록/초대/수정/재초대).
 */
class StoreManageController extends Controller
{
    use ResolvesSeller;

    private function ensureHq(Request $request): void
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureHq($request);
        $stores = Store::with('account')->orderBy('name')->paginate(40);

        return response()->json([
            'data' => $stores->getCollection()->map(fn (Store $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'region' => $s->region,
                'email' => $s->email,
                'phone' => $s->phone,
                'address' => $s->address,
                'is_active' => (bool) $s->is_active,
                'invited' => $s->account && $s->account->invite_token !== null,
                'joined' => $s->account && $s->account->invite_token === null,
            ])->values(),
        ]);
    }

    public function invite(Request $request): JsonResponse
    {
        $this->ensureHq($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'region' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ], ['email.unique' => '이미 사용 중인 이메일입니다.']);

        $store = Store::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'region' => $data['region'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'is_active' => true,
        ]);
        $this->issueInvite($store, $data['email'], $data['name']);

        return response()->json(['message' => "{$data['name']} 매장을 초대했습니다."], 201);
    }

    public function update(Request $request, Store $store): JsonResponse
    {
        $this->ensureHq($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $store->update([
            ...$data,
            'is_active' => $request->boolean('is_active', $store->is_active),
        ]);

        return response()->json(['message' => '매장이 수정되었습니다.']);
    }

    public function reinvite(Request $request, Store $store): JsonResponse
    {
        $this->ensureHq($request);
        if (! $store->email) {
            return response()->json(['message' => '매장 이메일이 없습니다.'], 422);
        }
        $existing = User::where('email', $store->email)->first();
        if ($existing && ! $existing->invite_token) {
            return response()->json(['message' => '이미 가입 완료된 계정입니다.'], 409);
        }
        $this->issueInvite($store, $store->email, $store->name);

        return response()->json(['message' => '초대 메일을 재발송했습니다.']);
    }

    private function issueInvite(Store $store, string $email, string $name): void
    {
        $token = Str::random(48);
        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name . ' 점주',
                'password' => Hash::make(Str::random(32)),
                'role' => 'store',
                'store_id' => $store->id,
                'invite_token' => $token,
                'invited_at' => now(),
            ]
        );
        try {
            Mail::to($email)->send(new PortalInvitation($store->name, '매장', route('portal.invite.show', $token)));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
