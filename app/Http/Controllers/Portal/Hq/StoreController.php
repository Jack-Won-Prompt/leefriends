<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Mail\PortalInvitation;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::with('account')->orderBy('name')->paginate(20);

        return view('portal.hq.stores.index', compact('stores'));
    }

    /** 신규 매장을 이메일로 초대 (매장 생성 + 초대 메일) */
    public function invite(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'region' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ], [
            'email.unique' => '이미 사용 중인 이메일입니다.',
            'email.required' => '초대할 이메일을 입력해 주세요.',
        ]);

        $store = Store::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'region' => $data['region'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'is_active' => true,
        ]);

        $this->issueInvite($store, $data['email'], $data['name']);

        return redirect()->route('portal.hq.stores.index')
            ->with('success', "{$data['name']}({$data['email']})에게 초대 메일을 발송했습니다. 매장이 비밀번호를 설정하면 포털을 사용할 수 있습니다.");
    }

    /** 기존 매장에 초대 메일 재발송 */
    public function reinvite(Store $store)
    {
        if (! $store->email) {
            return back()->withErrors(['email' => '매장 이메일이 없습니다. 먼저 이메일을 등록해 주세요.']);
        }
        $existing = User::where('email', $store->email)->first();
        if ($existing && ! $existing->invite_token) {
            return back()->withErrors(['email' => '이미 비밀번호 설정이 완료된 계정입니다.']);
        }

        $this->issueInvite($store, $store->email, $store->name);

        return back()->with('success', "{$store->name}({$store->email})에게 초대 메일을 재발송했습니다.");
    }

    /** 초대 토큰 발급(계정 생성/갱신) + 메일 발송 */
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

        Mail::to($email)->send(new PortalInvitation($store->name, '매장', route('portal.invite.show', $token)));
    }
}
