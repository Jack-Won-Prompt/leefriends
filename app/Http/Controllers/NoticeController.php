<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('cat', 'all');

        $query = Notice::published()->orderByDesc('is_pinned')->orderByDesc('published_at');
        if ($category !== 'all' && array_key_exists($category, Notice::CATEGORIES)) {
            $query->where('category', $category);
        }
        $notices = $query->paginate(8)->withQueryString();

        return view('notice.index', [
            'notices' => $notices,
            'category' => $category,
            'categories' => Notice::CATEGORIES,
        ]);
    }

    public function show(Notice $notice)
    {
        $notice->increment('views');

        $prev = Notice::published()->where('id', '<', $notice->id)->orderByDesc('id')->first();
        $next = Notice::published()->where('id', '>', $notice->id)->orderBy('id')->first();

        return view('notice.show', compact('notice', 'prev', 'next'));
    }
}
