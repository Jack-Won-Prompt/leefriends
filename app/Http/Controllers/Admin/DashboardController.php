<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FranchiseInquiry;
use App\Models\Menu;
use App\Models\Notice;
use App\Models\Store;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'inquiries' => FranchiseInquiry::count(),
            'new_inquiries' => FranchiseInquiry::where('status', 'new')->count(),
            'menus' => Menu::count(),
            'stores' => Store::count(),
            'notices' => Notice::count(),
        ];

        $recentInquiries = FranchiseInquiry::latest()->take(6)->get();

        return view('admin.dashboard', compact('stats', 'recentInquiries'));
    }
}
