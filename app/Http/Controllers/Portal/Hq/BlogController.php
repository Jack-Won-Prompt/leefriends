<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Services\Content\NaverContentService;
use Illuminate\Http\Request;

/** 본사 포털 — 홈페이지 블로그 관리 (공식 네이버 블로그 RSS 자동수집) */
class BlogController extends Controller
{
    public function index()
    {
        $posts = BlogPost::orderBy('sort_order')->orderByDesc('posted_at')->paginate(20);
        $blogId = config('services.naver.blog_id');

        return view('portal.hq.blog.index', compact('posts', 'blogId'));
    }

    public function sync(NaverContentService $service)
    {
        try {
            $r = $service->syncBlogPosts();
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage() ?: '블로그 업데이트에 실패했습니다.');
        }

        $msg = $r['added'] > 0
            ? "새 블로그 글 {$r['added']}건을 가져왔습니다. (전체 {$r['total']}건)"
            : "새로 추가된 글이 없습니다. (전체 {$r['total']}건)";

        return back()->with('success', $msg);
    }

    public function update(Request $request, BlogPost $blog)
    {
        $blog->update($request->validate([
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', '블로그 글이 수정되었습니다.');
    }

    public function destroy(BlogPost $blog)
    {
        $blog->delete();

        return back()->with('success', '블로그 글이 삭제되었습니다.');
    }
}
