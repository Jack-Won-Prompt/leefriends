<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\NaverClip;
use Illuminate\Http\JsonResponse;

/**
 * 공개 콘텐츠 피드 — 네이버 블로그(RSS 자동수집) / 네이버 클립.
 * 공개 홈(PageController@home)과 동일한 데이터를 앱에 제공.
 */
class ContentFeedController extends Controller
{
    /** GET /api/v1/blog-posts */
    public function blogPosts(): JsonResponse
    {
        $posts = BlogPost::active()
            ->orderBy('sort_order')
            ->orderByDesc('posted_at')
            ->take(20)
            ->get()
            ->map(fn (BlogPost $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'url' => $p->url,
                'thumbnail' => $p->thumbnail_url,
                'summary' => $p->summary,
                'posted_at' => $p->posted_at?->format('Y-m-d'),
            ]);

        return response()->json(['data' => $posts->values()]);
    }

    /** GET /api/v1/naver-clips */
    public function clips(): JsonResponse
    {
        $clips = NaverClip::active()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->take(20)
            ->get()
            ->map(fn (NaverClip $c) => [
                'id' => $c->id,
                'title' => $c->title,
                'url' => $c->url,
                'thumbnail' => $c->thumbnail_url,
            ]);

        return response()->json(['data' => $clips->values()]);
    }
}
