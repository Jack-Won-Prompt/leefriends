<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\NaverClip;
use App\Services\Content\NaverContentService;
use Illuminate\Http\Request;

/** 본사 포털 — 홈페이지 네이버 클립 관리 (수동 등록) */
class ClipController extends Controller
{
    public function index()
    {
        $clips = NaverClip::orderBy('sort_order')->orderByDesc('id')->paginate(20);

        return view('portal.hq.clips.index', compact('clips'));
    }

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
