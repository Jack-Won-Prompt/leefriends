<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * 공급처 초대 수락 — 초대 토큰으로 비밀번호 설정 후 포털 사용.
 */
class InvitationController extends Controller
{
    public function show(string $token)
    {
        $user = User::where('invite_token', $token)->first();

        return view('portal.invite', ['user' => $user, 'token' => $token]);
    }

    public function accept(Request $request, string $token)
    {
        $user = User::where('invite_token', $token)->first();
        if (! $user) {
            return redirect()->route('portal.login')->withErrors(['email' => '유효하지 않거나 만료된 초대 링크입니다.']);
        }

        $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'password.confirmed' => '비밀번호 확인이 일치하지 않습니다.',
            'password.min' => '비밀번호는 8자 이상이어야 합니다.',
        ]);

        $user->update([
            'password' => Hash::make($request->input('password')),
            'invite_token' => null,
            'invited_at' => null,
            'email_verified_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('portal.dashboard')->with('success', '비밀번호가 설정되었습니다. 공급처 포털에 오신 것을 환영합니다!');
    }
}
