<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index()
    {
        $notices = Notice::latest()->paginate(15);

        return view('admin.notices.index', compact('notices'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        Notice::create($data);

        return redirect()->route('admin.notices.index')->with('success', '공지가 등록되었습니다.');
    }

    public function update(Request $request, Notice $notice)
    {
        $notice->update($this->validateData($request));

        return redirect()->route('admin.notices.index')->with('success', '공지가 수정되었습니다.');
    }

    public function destroy(Notice $notice)
    {
        $notice->delete();

        return back()->with('success', '공지가 삭제되었습니다.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'category' => ['required', 'in:notice,news,event'],
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ]);
        $data['is_pinned'] = $request->boolean('is_pinned');
        $data['published_at'] = $data['published_at'] ?? now();

        return $data;
    }
}
