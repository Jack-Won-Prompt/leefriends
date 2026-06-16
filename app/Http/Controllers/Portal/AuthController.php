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
            if (! $this->portalRole(Auth::user())) {
                Auth::logout();
                return back()->withErrors(['email' => '포털 사용 권한이 없는 계정입니다.'])->onlyInput('email');
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
