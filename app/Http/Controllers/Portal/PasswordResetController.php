<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/** 발주포털 비밀번호 찾기(이메일 재설정) */
class PasswordResetController extends Controller
{
    /** 비밀번호 찾기 폼 */
    public function request()
    {
        return view('portal.auth.forgot-password');
    }

    /** 재설정 링크 이메일 발송 */
    public function email(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', '비밀번호 재설정 링크를 이메일로 보냈습니다. 메일함을 확인해 주세요.');
        }

        return back()->withErrors(['email' => '해당 이메일로 등록된 계정을 찾을 수 없습니다.'])->onlyInput('email');
    }

    /** 재설정 폼 (메일 링크) */
    public function reset(Request $request, string $token)
    {
        return view('portal.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /** 새 비밀번호 저장 */
    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:4'],
        ], [
            'password.confirmed' => '비밀번호 확인이 일치하지 않습니다.',
            'password.min' => '비밀번호는 4자 이상이어야 합니다.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('portal.login')->with('status', '비밀번호가 변경되었습니다. 새 비밀번호로 로그인해 주세요.');
        }

        return back()->withErrors(['email' => '재설정 링크가 만료되었거나 올바르지 않습니다. 다시 시도해 주세요.']);
    }
}
