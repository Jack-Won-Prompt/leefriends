<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\SupplyProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * 품목 카테고리(대분류) 관리 — 본사 전용.
 */
class CategoryController extends Controller
{
    use ResolvesSeller;

    private function ensureHq(Request $request): void
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403, '본사 계정만 사용할 수 있습니다.');
    }

    public function index(Request $request): JsonResponse
    {
        $this->ensureHq($request);
        $data = ProductCategory::ordered()->get()->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'code' => $c->code,
            'sort_order' => $c->sort_order,
            'product_count' => SupplyProduct::where('category', $c->name)->count(),
        ]);

        return response()->json(['data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureHq($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('product_categories', 'name')],
            'code' => ['nullable', 'string', 'max:10', 'alpha_num', Rule::unique('product_categories', 'code')],
            'sort_order' => ['nullable', 'integer'],
        ]);

        ProductCategory::create([
            'name' => $data['name'],
            'code' => strtoupper($data['code'] ?? $this->genCode($data['name'])),
            'sort_order' => $data['sort_order'] ?? ((int) ProductCategory::max('sort_order') + 1),
        ]);

        return response()->json(['message' => '카테고리를 추가했습니다.'], 201);
    }

    public function update(Request $request, ProductCategory $category): JsonResponse
    {
        $this->ensureHq($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('product_categories', 'name')->ignore($category->id)],
            'code' => ['required', 'string', 'max:10', 'alpha_num', Rule::unique('product_categories', 'code')->ignore($category->id)],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $data['code'] = strtoupper($data['code']);

        DB::transaction(function () use ($category, $data) {
            SupplyProduct::where('category', $category->name)
                ->update(['category' => $data['name'], 'category_code' => $data['code']]);
            $category->update([
                'name' => $data['name'],
                'code' => $data['code'],
                'sort_order' => $data['sort_order'] ?? $category->sort_order,
            ]);
        });

        return response()->json(['message' => '카테고리를 수정했습니다.']);
    }

    public function destroy(Request $request, ProductCategory $category): JsonResponse
    {
        $this->ensureHq($request);
        $count = SupplyProduct::where('category', $category->name)->count();
        if ($count > 0) {
            return response()->json(
                ['message' => "이 카테고리에 품목 {$count}개가 있어 삭제할 수 없습니다."], 409);
        }
        $category->delete();

        return response()->json(['message' => '카테고리를 삭제했습니다.']);
    }

    private function genCode(string $name): string
    {
        $map = SupplyProduct::CATEGORY_CODES;
        if (isset($map[$name])) {
            return $map[$name];
        }

        return 'C' . str_pad((string) (ProductCategory::count() + 1), 2, '0', STR_PAD_LEFT);
    }
}
