<?php

namespace App\Http\Controllers;

use App\Models\FranchiseInquiry;
use App\Models\Store;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;

class FranchiseController extends Controller
{
    public function index()
    {
        $storeCount = Store::active()->count();

        return view('franchise', compact('storeCount'));
    }

    public function store(Request $request, NotificationService $notifications)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'region' => ['nullable', 'string', 'max:50'],
            'budget' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:2000'],
            'agree_privacy' => ['accepted'],
        ], [
            'name.required' => '성함을 입력해 주세요.',
            'phone.required' => '연락처를 입력해 주세요.',
            'agree_privacy.accepted' => '개인정보 수집·이용에 동의해 주세요.',
        ]);

        $validated['agree_privacy'] = true;
        $validated['status'] = 'new';

        $inquiry = FranchiseInquiry::create($validated);

        // 본사 전원에게 실시간 알림(인앱+토스트) — 접수 즉시 확인 가능
        $notifications->notifyUsers(
            User::where('role', 'hq')->get(),
            'franchise_inquiry',
            '📨 새 창업 문의',
            "{$inquiry->name}님이 창업 문의를 남겼습니다." . ($inquiry->region ? " (희망지역: {$inquiry->region})" : ''),
            ['inquiry_id' => $inquiry->id],
        );

        return redirect()->route('franchise.thanks');
    }

    public function thanks()
    {
        return view('franchise-thanks');
    }
}
