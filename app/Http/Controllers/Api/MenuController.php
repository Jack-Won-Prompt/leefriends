<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * GET /api/v1/menus
     * Optional query: ?cat=signature|bingsu|drink|dessert
     */
    public function index(Request $request): JsonResponse
    {
        $category = $request->query('cat', 'all');

        $query = Menu::active()->orderBy('sort_order');
        if ($category !== 'all' && array_key_exists($category, Menu::CATEGORIES)) {
            $query->where('category', $category);
        }

        $menus = $query->get()->map(fn (Menu $menu) => $this->transform($menu));

        return response()->json([
            'data' => $menus,
            'meta' => [
                'count' => $menus->count(),
                'category' => $category,
            ],
        ]);
    }

    /**
     * GET /api/v1/menus/{menu}
     */
    public function show(Menu $menu): JsonResponse
    {
        return response()->json(['data' => $this->transform($menu)]);
    }

    /**
     * GET /api/v1/categories
     */
    public function categories(): JsonResponse
    {
        $data = collect(Menu::CATEGORIES)->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
        ])->values();

        return response()->json(['data' => $data]);
    }

    private function transform(Menu $menu): array
    {
        return [
            'id' => $menu->id,
            'category' => $menu->category,
            'category_label' => $menu->category_label,
            'name' => $menu->name,
            'name_en' => $menu->name_en,
            'description' => $menu->description,
            'price' => $menu->price,
            'image' => $menu->image ? asset($menu->image) : null,
            'badge' => $menu->badge,
            'sort_order' => $menu->sort_order,
        ];
    }
}
