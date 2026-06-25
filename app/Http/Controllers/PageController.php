<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Notice;
use App\Models\Store;

class PageController extends Controller
{
    public function home()
    {
        $signatures = Menu::active()->where('category', 'signature')->orderBy('sort_order')->get();
        $bests = Menu::active()->where('badge', 'best')->orderBy('sort_order')->take(4)->get();
        $populars = Menu::active()->orderBy('sort_order')->take(8)->get();
        $notices = Notice::published()->orderByDesc('is_pinned')->orderByDesc('published_at')->take(4)->get();
        $storeCount = Store::active()->count();

        return view('home', compact('signatures', 'bests', 'populars', 'notices', 'storeCount'));
    }

    public function brand()
    {
        return view('brand');
    }

    public function privacy()
    {
        return view('privacy');
    }
}
