<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FranchiseInquiry;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 가맹(창업) 문의 — 앱 소비자 공개 폼. 접수 시 본사에 알림.
 */
class FranchiseController extends Controller
{
    /** POST /api/v1/franchise-inquiry */
    public function store(Request $request, NotificationService $notifications): JsonResponse
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

        $notifications->notifyUsers(
            User::where('role', 'hq')->get(),
            'franchise_inquiry',
            '📨 새 창업 문의',
            "{$inquiry->name}님이 창업 문의를 남겼습니다.".($inquiry->region ? " (희망지역: {$inquiry->region})" : ''),
            ['inquiry_id' => $inquiry->id],
        );

        return response()->json([
            'message' => '창업 문의가 접수되었습니다. 담당자가 곧 연락드리겠습니다.',
        ], 201);
    }
}
