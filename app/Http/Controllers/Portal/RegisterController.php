<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * 이메일 자가 회원가입 — 제품 구매자(store) / 제품 공급자(supplier) 선택.
 * 가입 후 approval_status=pending 상태로 저장되며, 본사 승인 후에만 로그인 가능.
 */
class RegisterController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.register');
    }

    public function store(Request $request, NotificationService $notifier)
    {
        $data = $request->validate([
            'member_type' => ['required', Rule::in(array_keys(User::SIGNUP_TYPES))],
            'org_name' => ['required', 'string', 'max:100'],
            'contact_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:30'],
            'biz_no' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'member_type.required' => '회원 종류를 선택해 주세요.',
            'org_name.required' => '상호(회사명)를 입력해 주세요.',
            'contact_name.required' => '담당자명을 입력해 주세요.',
            'email.unique' => '이미 사용 중인 이메일입니다.',
            'phone.required' => '연락처를 입력해 주세요.',
            'password.confirmed' => '비밀번호 확인이 일치하지 않습니다.',
            'password.min' => '비밀번호는 8자 이상이어야 합니다.',
        ]);

        $role = $data['member_type']; // 'store' | 'supplier'

        $user = DB::transaction(function () use ($data, $role) {
            if ($role === 'store') {
                // 구매자 — 공개 매장 지도에 노출되지 않도록 is_active=false로 생성
                $org = Store::create([
                    'name' => $data['org_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'biz_no' => $data['biz_no'] ?? null,
                    'address' => $data['address'] ?? '',
                    'is_active' => false,
                ]);
                $orgKey = ['store_id' => $org->id];
            } else {
                $org = Supplier::create([
                    'name' => $data['org_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'biz_no' => $data['biz_no'] ?? null,
                    'address' => $data['address'] ?? '',
                    'is_active' => true,
                ]);
                $orgKey = ['supplier_id' => $org->id];
            }

            return User::create(array_merge([
                'name' => $data['contact_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => Hash::make($data['password']),
                'role' => $role,
                'approval_status' => User::APPROVAL_PENDING,
                'email_verified_at' => now(),
            ], $orgKey));
        });

        // 본사에 승인 요청 알림
        $typeLabel = User::SIGNUP_TYPES[$role];
        $notifier->notifyUsers(
            User::where('role', 'hq')->orWhere('is_admin', true)->get(),
            'registration_requested',
            '📝 새 회원가입 신청',
            "{$data['org_name']} · {$typeLabel} ({$data['email']})",
            ['user_id' => $user->id],
        );

        return redirect()->route('portal.login')
            ->with('status', '회원가입 신청이 접수되었습니다. 본사 승인 후 로그인하실 수 있습니다. 승인 결과는 이메일로 안내드립니다.');
    }
}
