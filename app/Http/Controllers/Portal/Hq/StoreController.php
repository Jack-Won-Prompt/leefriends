<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Mail\PortalInvitation;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /** 매장 삭제 (발주 이력이 있으면 차단 — 데이터 보존) */
    public function destroy(Store $store)
    {
        $orderCount = Order::where('store_id', $store->id)->count();
        if ($orderCount > 0) {
            return back()->withErrors(['store' => "«{$store->name}»은(는) 발주 이력 {$orderCount}건이 있어 삭제할 수 없습니다. 대신 비활성화하세요."]);
        }

        DB::transaction(function () use ($store) {
            // 매장 채팅방 + 메시지(FK cascade) 정리
            Conversation::where('party_type', 'store')->where('party_id', $store->id)->delete();
            // 매장 계정 정리
            User::where('store_id', $store->id)->delete();
            // 재고/이동 정리
            DB::table('store_inventories')->where('store_id', $store->id)->delete();
            DB::table('inventory_movements')->where('store_id', $store->id)->delete();
            $store->delete();
        });

        return redirect()->route('portal.hq.stores.index')->with('success', "매장 «{$store->name}»을(를) 삭제했습니다.");
    }

    /** 매장 기본정보 수정 (이름·연락처·이메일·주소 등) */
    public function update(Request $request, Store $store)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $store->update($data);

        return back()->with('success', "«{$store->name}» 정보를 수정했습니다.");
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
