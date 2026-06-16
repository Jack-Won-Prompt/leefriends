<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Mail\PortalInvitation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::withCount('products')->with('account')->orderBy('name')->paginate(20);

        return view('portal.hq.suppliers.index', compact('suppliers'));
    }

    /** 신규 공급처를 이메일로 초대 (공급처 생성 + 초대 메일) */
    public function invite(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'biz_no' => ['nullable', 'string', 'max:30'],
            'ceo' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
        ], [
            'email.unique' => '이미 사용 중인 이메일입니다.',
            'email.required' => '초대할 이메일을 입력해 주세요.',
        ]);

        $supplier = Supplier::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'biz_no' => $data['biz_no'] ?? null,
            'ceo' => $data['ceo'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => true,
        ]);

        $this->issueInvite($supplier, $data['email'], $data['name']);

        return redirect()->route('portal.hq.suppliers.index')
            ->with('success', "{$data['name']}({$data['email']})에게 초대 메일을 발송했습니다. 공급처가 비밀번호를 설정하면 포털을 사용할 수 있습니다.");
    }

    /** 기존 공급처에 초대 메일 재발송 */
    public function reinvite(Supplier $supplier)
    {
        if (! $supplier->email) {
            return back()->withErrors(['email' => '공급처 이메일이 없습니다. 먼저 이메일을 등록해 주세요.']);
        }
        $existing = User::where('email', $supplier->email)->first();
        if ($existing && ! $existing->invite_token) {
            return back()->withErrors(['email' => '이미 비밀번호 설정이 완료된 계정입니다.']);
        }

        $this->issueInvite($supplier, $supplier->email, $supplier->name);

        return back()->with('success', "{$supplier->name}({$supplier->email})에게 초대 메일을 재발송했습니다.");
    }

    /** 초대 토큰 발급(계정 생성/갱신) + 메일 발송 */
    private function issueInvite(Supplier $supplier, string $email, string $name): void
    {
        $token = Str::random(48);
        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name . ' 담당자',
                'password' => Hash::make(Str::random(32)), // 임시(미사용) — 초대 수락 시 본인이 설정
                'role' => 'supplier',
                'supplier_id' => $supplier->id,
                'invite_token' => $token,
                'invited_at' => now(),
            ]
        );

        Mail::to($email)->send(new PortalInvitation($supplier->name, '공급처', route('portal.invite.show', $token)));
    }

    public function store(Request $request)
    {
        Supplier::create($this->validateData($request));

        return redirect()->route('portal.hq.suppliers.index')->with('success', '공급처가 등록되었습니다.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($this->validateData($request));

        return redirect()->route('portal.hq.suppliers.index')->with('success', '공급처가 수정되었습니다.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return back()->with('success', '공급처가 삭제되었습니다.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'biz_no' => ['nullable', 'string', 'max:30'],
            'ceo' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'return_postcode' => ['nullable', 'string', 'max:20'],
            'return_address' => ['nullable', 'string', 'max:255'],
            'return_address_detail' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
