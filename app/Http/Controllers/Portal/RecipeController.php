<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Recipe;

/**
 * 매장/공급처 — 레시피 열람 (상세는 팝업).
 */
class RecipeController extends Controller
{
    public function index()
    {
        return view('portal.recipes.index', [
            'recipes' => Recipe::with('product')->sorted()->paginate(24),
        ]);
    }
}
