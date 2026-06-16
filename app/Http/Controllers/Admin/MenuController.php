<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::orderBy('sort_order')->paginate(20);

        return view('admin.menus.index', compact('menus'));
    }

    public function store(Request $request)
    {
        Menu::create($this->validateData($request));

        return redirect()->route('admin.menus.index')->with('success', '메뉴가 등록되었습니다.');
    }

    public function update(Request $request, Menu $menu)
    {
        $menu->update($this->validateData($request));

        return redirect()->route('admin.menus.index')->with('success', '메뉴가 수정되었습니다.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();

        return back()->with('success', '메뉴가 삭제되었습니다.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'category' => ['required', 'in:signature,bingsu,drink,dessert'],
            'name' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'in:best,new,hot'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['badge'] = ($data['badge'] ?? null) ?: null;
        $data['image'] = ($data['image'] ?? null) ?: 'images/menu/mango-cheese-bingsu.svg';

        return $data;
    }
}
