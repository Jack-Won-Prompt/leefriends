<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NaverClip;
use App\Services\Content\NaverContentService;
use Illuminate\Http\Request;

class NaverClipController extends Controller
{
    public function index()
    {
        $clips = NaverClip::orderBy('sort_order')->orderByDesc('id')->paginate(20);

        return view('admin.clips.index', compact('clips'));
    }

    /** 클립 등록 — URL 붙여넣으면 og:title / og:image 자동 추출 */
    public function store(Request $request, NaverContentService $service)
    {
        $data = $request->validate([
            'url' => ['required', 'url', 'max:500'],
            'title' => ['nullable', 'string', 'max:255'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        if (empty($data['title']) || empty($data['thumbnail'])) {
            $meta = $service->fetchClipMeta($data['url']);
            $data['title'] = $data['title'] ?: ($meta['title'] ?: '네이버 클립');
            $data['thumbnail'] = $data['thumbnail'] ?: $meta['thumbnail'];
        }

        // 썸네일 이미지를 서버로 다운로드 (핫링크 차단·만료 방지). 실패 시 원격 URL 유지
        $local = $service->downloadClipThumbnail($data['thumbnail'] ?? null);

        NaverClip::create([
            'title' => $data['title'],
            'url' => $data['url'],
            'thumbnail' => $local ?: ($data['thumbnail'] ?? null),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', '네이버 클립이 등록되었습니다.');
    }

    public function update(Request $request, NaverClip $clip, NaverContentService $service)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'thumbnail' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        // 새 썸네일이 원격 URL 이면 서버로 다운로드
        if (! empty($data['thumbnail']) && str_starts_with($data['thumbnail'], 'http')) {
            $data['thumbnail'] = $service->downloadClipThumbnail($data['thumbnail']) ?: $data['thumbnail'];
        }

        $clip->update($data);

        return back()->with('success', '네이버 클립이 수정되었습니다.');
    }

    public function destroy(NaverClip $clip)
    {
        $clip->delete();

        return back()->with('success', '네이버 클립이 삭제되었습니다.');
    }
}
