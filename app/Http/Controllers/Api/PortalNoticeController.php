<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PortalNotice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 매장/공급처 — 본사 포털 공지사항 열람 (앱).
 * 웹 포털 «공지사항»(Portal\NoticeController)과 같은 조건.
 */
class PortalNoticeController extends Controller
{
    private function role(Request $request): string
    {
        $role = $request->user()->role;
        abort_unless(in_array($role, ['store', 'supplier'], true), 403, '매장/공급처 계정만 사용할 수 있습니다.');

        return $role;
    }

    /** GET /api/v1/portal-notices */
    public function index(Request $request): JsonResponse
    {
        $this->role($request); // 역할 검증
        $notices = PortalNotice::visibleTo($request->user())->sorted()->paginate(20);

        return response()->json([
            'data' => $notices->getCollection()->map(fn (PortalNotice $n) => $this->present($n))->values(),
            'meta' => [
                'current_page' => $notices->currentPage(),
                'last_page' => $notices->lastPage(),
                'total' => $notices->total(),
            ],
        ]);
    }

    /** GET /api/v1/portal-notices/{notice} */
    public function show(Request $request, PortalNotice $notice): JsonResponse
    {
        $this->role($request); // 역할 검증
        abort_unless($notice->isVisibleTo($request->user()), 403);

        return response()->json(['data' => $this->present($notice)]);
    }

    private function present(PortalNotice $n): array
    {
        return [
            'id' => $n->id,
            'title' => $n->title,
            'content' => $n->content,
            'is_pinned' => (bool) $n->is_pinned,
            'created_at' => $n->created_at?->format('Y-m-d H:i'),
        ];
    }
}
