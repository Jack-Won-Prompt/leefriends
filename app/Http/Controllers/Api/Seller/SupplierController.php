<?php

namespace App\Http\Controllers\Api\Seller;

use App\Mail\PortalInvitation;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * 공급처 관리 — 본사 전용 (목록/초대/수정/삭제/재초대).
 */
class SupplierController extends Controller
{
    use ResolvesSeller;

    private function ensureHq(Request $request): void
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureHq($request);
        $suppliers = Supplier::withCount('products')->with('account')->orderBy('name')->paginate(40);

        return response()->json([
            'data' => $suppliers->getCollection()->map(fn (Supplier $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'email' => $s->email,
                'ceo' => $s->ceo,
                'phone' => $s->phone,
                'biz_no' => $s->biz_no,
                'product_count' => $s->products_count,
                'is_active' => (bool) $s->is_active,
                'invited' => $s->account && $s->account->invite_token !== null,
                'joined' => $s->account && $s->account->invite_token === null,
            ])->values(),
        ]);
    }

    public function invite(Request $request): JsonResponse
    {
        $this->ensureHq($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'biz_no' => ['nullable', 'string', 'max:30'],
            'ceo' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
        ], ['email.unique' => '이미 사용 중인 이메일입니다.']);

        $supplier = Supplier::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'biz_no' => $data['biz_no'] ?? null,
            'ceo' => $data['ceo'] ?? null,
            'phone' => $data['phone'] ?? null,
            'is_active' => true,
        ]);
        $this->issueInvite($supplier, $data['email'], $data['name']);

        return response()->json(['message' => "{$data['name']} 공급처를 초대했습니다."], 201);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $this->ensureHq($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'biz_no' => ['nullable', 'string', 'max:30'],
            'ceo' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $supplier->update([
            ...$data,
            'is_active' => $request->boolean('is_active', $supplier->is_active),
        ]);

        return response()->json(['message' => '공급처가 수정되었습니다.']);
    }

    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        $this->ensureHq($request);
        $supplier->delete();

        return response()->json(['message' => '공급처가 삭제되었습니다.']);
    }

    public function reinvite(Request $request, Supplier $supplier): JsonResponse
    {
        $this->ensureHq($request);
        if (! $supplier->email) {
            return response()->json(['message' => '공급처 이메일이 없습니다.'], 422);
        }
        $existing = User::where('email', $supplier->email)->first();
        if ($existing && ! $existing->invite_token) {
            return response()->json(['message' => '이미 가입 완료된 계정입니다.'], 409);
        }
        $this->issueInvite($supplier, $supplier->email, $supplier->name);

        return response()->json(['message' => '초대 메일을 재발송했습니다.']);
    }

    private function issueInvite(Supplier $supplier, string $email, string $name): void
    {
        $token = Str::random(48);
        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name . ' 담당자',
                'password' => Hash::make(Str::random(32)),
                'role' => 'supplier',
                'supplier_id' => $supplier->id,
                'invite_token' => $token,
                'invited_at' => now(),
            ]
        );
        try {
            Mail::to($email)->send(new PortalInvitation($supplier->name, '공급처', route('portal.invite.show', $token)));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
