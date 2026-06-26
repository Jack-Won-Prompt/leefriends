<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * 직원(계정) 관리 — 본사/매장/공급처 각자 소속 직원 계정을 등록·관리.
 * 같은 역할(role) + 같은 소속(store_id / supplier_id) 범위로만 조회·관리된다.
 */
class StaffController extends Controller
{
    public function index()
    {
        return view('portal.staff.index', [
            'staff' => $this->orgQuery()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:4', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'email.unique' => '이미 사용 중인 이메일입니다.',
            'password.required' => '임시 비밀번호를 입력해 주세요.',
        ]);

        $me = Auth::user();
        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'], // 모델 cast(hashed)로 자동 해시
            'role' => $me->role,
            'store_id' => $me->store_id,
            'supplier_id' => $me->supplier_id,
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        return back()->with('success', "직원 «{$data['name']}» 계정을 등록했습니다. (임시 비밀번호로 로그인 후 변경 안내)");
    }

    public function update(Request $request, User $user)
    {
        $this->assertSameOrg($user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
        ], ['email.unique' => '이미 사용 중인 이메일입니다.']);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        if (! empty($data['password'])) {
            $user->password = $data['password']; // 비밀번호 재설정(입력 시에만)
        }
        $user->save();

        return back()->with('success', "«{$user->name}» 계정을 수정했습니다.");
    }

    public function destroy(User $user)
    {
        $this->assertSameOrg($user);
        abort_if($user->id === Auth::id(), 400, '본인 계정은 삭제할 수 없습니다.');

        $user->delete();

        return back()->with('success', '직원 계정을 삭제했습니다.');
    }

    /** 현재 로그인 사용자의 소속(역할+조직) 범위로 한정한 User 쿼리 */
    private function orgQuery()
    {
        $me = Auth::user();
        $q = User::where('role', $me->role);

        if ($me->role === 'store') {
            $q->where('store_id', $me->store_id);
        } elseif ($me->role === 'supplier') {
            $q->where('supplier_id', $me->supplier_id);
        }

        return $q;
    }

    /** 대상 사용자가 같은 소속인지 검증 */
    private function assertSameOrg(User $user): void
    {
        $me = Auth::user();
        $ok = $user->role === $me->role
            && (int) $user->store_id === (int) $me->store_id
            && (int) $user->supplier_id === (int) $me->supplier_id;

        abort_unless($ok, 403);
    }
}
