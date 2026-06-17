<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 소비자 공지/뉴스/이벤트 (공개).
 */
class NoticeController extends Controller
{
    /**
     * GET /api/v1/notices?cat=all|notice|news|event
     */
    public function index(Request $request): JsonResponse
    {
        $category = $request->query('cat', 'all');

        $query = Notice::published()->orderByDesc('is_pinned')->orderByDesc('published_at');
        if ($category !== 'all' && array_key_exists($category, Notice::CATEGORIES)) {
            $query->where('category', $category);
        }
        $notices = $query->paginate(10);

        return response()->json([
            'data' => $notices->getCollection()->map(fn (Notice $n) => $this->summary($n))->values(),
            'meta' => [
                'category' => $category,
                'categories' => collect(Notice::CATEGORIES)
                    ->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
                'current_page' => $notices->currentPage(),
                'last_page' => $notices->lastPage(),
                'total' => $notices->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/notices/{notice}
     */
    public function show(Notice $notice): JsonResponse
    {
        $notice->increment('views');

        return response()->json([
            'data' => array_merge($this->summary($notice), [
                'content' => $notice->content,
            ]),
        ]);
    }

    private function summary(Notice $n): array
    {
        return [
            'id' => $n->id,
            'category' => $n->category,
            'category_label' => Notice::CATEGORIES[$n->category] ?? $n->category,
            'title' => $n->title,
            'is_pinned' => (bool) $n->is_pinned,
            'views' => (int) $n->views,
            'published_at' => $n->published_at?->format('Y-m-d'),
        ];
    }
}
