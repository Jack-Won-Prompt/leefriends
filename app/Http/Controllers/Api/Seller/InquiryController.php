<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\FranchiseInquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 가맹문의 처리 — 본사 전용 (목록/상세/상태변경/삭제).
 */
class InquiryController extends Controller
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
        $status = $request->query('status', 'all');

        $query = FranchiseInquiry::latest();
        if (array_key_exists($status, FranchiseInquiry::STATUSES)) {
            $query->where('status', $status);
        }
        $inquiries = $query->paginate(30);

        return response()->json([
            'data' => $inquiries->getCollection()->map(fn (FranchiseInquiry $q) => $this->row($q))->values(),
            'meta' => [
                'status' => $status,
                'statuses' => collect(FranchiseInquiry::STATUSES)
                    ->map(fn ($l, $k) => ['key' => $k, 'label' => $l])->values(),
                'new_count' => FranchiseInquiry::where('status', 'new')->count(),
            ],
        ]);
    }

    public function show(Request $request, FranchiseInquiry $inquiry): JsonResponse
    {
        $this->ensureHq($request);

        return response()->json(['data' => array_merge($this->row($inquiry), [
            'message' => $inquiry->message,
            'email' => $inquiry->email,
            'budget' => $inquiry->budget,
        ])]);
    }

    public function update(Request $request, FranchiseInquiry $inquiry): JsonResponse
    {
        $this->ensureHq($request);
        $data = $request->validate(['status' => ['required', 'in:new,contacted,done']]);
        $inquiry->update($data);

        return response()->json(['message' => '문의 상태가 변경되었습니다.', 'data' => $this->row($inquiry->fresh())]);
    }

    public function destroy(Request $request, FranchiseInquiry $inquiry): JsonResponse
    {
        $this->ensureHq($request);
        $inquiry->delete();

        return response()->json(['message' => '문의를 삭제했습니다.']);
    }

    private function row(FranchiseInquiry $q): array
    {
        return [
            'id' => $q->id,
            'name' => $q->name,
            'phone' => $q->phone,
            'region' => $q->region,
            'status' => $q->status,
            'status_label' => FranchiseInquiry::STATUSES[$q->status] ?? $q->status,
            'created_at' => $q->created_at?->format('Y-m-d H:i'),
        ];
    }
}
