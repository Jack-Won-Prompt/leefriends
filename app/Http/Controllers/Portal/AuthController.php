<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && $this->portalRole(Auth::user())) {
            return redirect()->route('portal.dashboard');
        }

        return view('portal.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (! $this->portalRole($user)) {
                Auth::logout();
                return back()->withErrors(['email' => '포털 사용 권한이 없는 계정입니다.'])->onlyInput('email');
            }

            // 본사 승인 게이트 — 대기/반려 계정은 로그인 차단
            if ($user->approval_status === \App\Models\User::APPROVAL_PENDING) {
                Auth::logout();
                return back()->withErrors(['email' => '본사 승인 대기 중인 계정입니다. 승인 완료 후 이용하실 수 있습니다.'])->onlyInput('email');
            }
            if ($user->approval_status === \App\Models\User::APPROVAL_REJECTED) {
                Auth::logout();
                $reason = $user->rejected_reason ? " (사유: {$user->rejected_reason})" : '';
                return back()->withErrors(['email' => "가입 신청이 반려된 계정입니다.{$reason}"])->onlyInput('email');
            }

            $request->session()->regenerate();

            return redirect()->intended(route('portal.dashboard'));
        }

        return back()->withErrors(['email' => '이메일 또는 비밀번호가 올바르지 않습니다.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    private function portalRole($user): ?string
    {
        $role = $user->role ?: ($user->is_admin ? 'hq' : null);

        return in_array($role, ['hq', 'store', 'supplier'], true) ? $role : null;
    }
}
