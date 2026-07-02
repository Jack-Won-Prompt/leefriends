<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 모바일 앱 — 직원 계정 관리. 같은 역할+소속 범위. 아르바이트는 불가(정직원/관리자만).
 * 웹 Portal\StaffController 와 동일 로직.
 */
class StaffController extends Controller
{
    private function guard(Request $request): void
    {
        abort_if($request->user()->isPartTime(), 403, '직원 관리는 정직원만 사용할 수 있습니다.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->guard($request);
        $me = $request->user();
        $q = User::where('role', $me->role);
        if ($me->role === 'store') {
            $q->where('store_id', $me->store_id);
        } elseif ($me->role === 'supplier') {
            $q->where('supplier_id', $me->supplier_id);
        }

        return response()->json([
            'employment_types' => collect(User::EMPLOYMENT_TYPES)
                ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'data' => $q->orderBy('name')->get()->map(fn (User $u) => $this->row($u, $me))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->guard($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:4', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'employment_type' => ['required', Rule::in(array_keys(User::EMPLOYMENT_TYPES))],
            'hourly_wage' => ['nullable', 'required_if:employment_type,part_time', 'integer', 'min:0', 'max:1000000'],
        ], [
            'email.unique' => '이미 사용 중인 이메일입니다.',
            'password.required' => '임시 비밀번호를 입력해 주세요.',
            'hourly_wage.required_if' => '아르바이트는 시급을 입력해 주세요.',
        ]);

        $me = $request->user();
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => $me->role,
            'employment_type' => $data['employment_type'],
            'hourly_wage' => $data['employment_type'] === 'part_time' ? (int) $data['hourly_wage'] : null,
            'store_id' => $me->store_id,
            'supplier_id' => $me->supplier_id,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => "직원 '{$data['name']}' 계정을 등록했습니다.",
            'data' => $this->row($user, $me),
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->guard($request);
        $this->assertSameOrg($request, $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
            'employment_type' => ['required', Rule::in(array_keys(User::EMPLOYMENT_TYPES))],
            'hourly_wage' => ['nullable', 'required_if:employment_type,part_time', 'integer', 'min:0', 'max:1000000'],
        ], [
            'email.unique' => '이미 사용 중인 이메일입니다.',
            'hourly_wage.required_if' => '아르바이트는 시급을 입력해 주세요.',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        $user->employment_type = $data['employment_type'];
        $user->hourly_wage = $data['employment_type'] === 'part_time' ? (int) $data['hourly_wage'] : null;
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return response()->json([
            'message' => "'{$user->name}' 계정을 수정했습니다.",
            'data' => $this->row($user->fresh(), $request->user()),
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->guard($request);
        $this->assertSameOrg($request, $user);
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => '본인 계정은 삭제할 수 없습니다.'], 422);
        }
        $user->delete();

        return response()->json(['message' => '직원 계정을 삭제했습니다.']);
    }

    private function assertSameOrg(Request $request, User $user): void
    {
        $me = $request->user();
        $ok = $user->role === $me->role
            && (int) $user->store_id === (int) $me->store_id
            && (int) $user->supplier_id === (int) $me->supplier_id;
        abort_unless($ok, 403);
    }

    private function row(User $u, User $me): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'employment_type' => $u->employment_type ?? 'regular',
            'employment_label' => $u->employment_label,
            'hourly_wage' => (int) ($u->hourly_wage ?? 0),
            'is_admin' => (bool) $u->is_admin,
            'is_self' => $u->id === $me->id,
        ];
    }
}
