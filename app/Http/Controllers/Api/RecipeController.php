<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 매장/공급처 — 본사 레시피 열람 (앱).
 * 웹 포털 «레시피»(Portal\RecipeController)와 같은 읽기 전용 화면.
 */
class RecipeController extends Controller
{
    private function ensureRole(Request $request): void
    {
        $role = $request->user()->role;
        abort_unless(in_array($role, ['store', 'supplier'], true), 403, '매장/공급처 계정만 사용할 수 있습니다.');
    }

    /** GET /api/v1/recipes */
    public function index(Request $request): JsonResponse
    {
        $this->ensureRole($request);
        $recipes = Recipe::with('product')->sorted()->paginate(20);

        return response()->json([
            'data' => $recipes->getCollection()->map(fn (Recipe $r) => $this->present($r))->values(),
            'meta' => [
                'current_page' => $recipes->currentPage(),
                'last_page' => $recipes->lastPage(),
                'total' => $recipes->total(),
            ],
        ]);
    }

    /** GET /api/v1/recipes/{recipe} */
    public function show(Request $request, Recipe $recipe): JsonResponse
    {
        $this->ensureRole($request);

        return response()->json(['data' => $this->present($recipe)]);
    }

    private function present(Recipe $r): array
    {
        return [
            'id' => $r->id,
            'title' => $r->title,
            'product_name' => $r->product_name ?: (optional($r->product)->name),
            'content' => $r->content,
            'image_url' => $r->image_url,
            'created_at' => $r->created_at?->format('Y-m-d H:i'),
        ];
    }
}
