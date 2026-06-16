<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('cat', 'all');

        $query = Menu::active()->orderBy('sort_order');
        if ($category !== 'all' && array_key_exists($category, Menu::CATEGORIES)) {
            $query->where('category', $category);
        }
        $menus = $query->get();

        return view('menu', [
            'menus' => $menus,
            'category' => $category,
            'categories' => Menu::CATEGORIES,
        ]);
    }
}
