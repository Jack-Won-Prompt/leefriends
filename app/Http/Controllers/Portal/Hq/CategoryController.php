<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\SupplyProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * 기준정보 — 품목 대분류(카테고리) 관리.
 */
class CategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::ordered()->get()->map(function ($c) {
            $c->product_count = SupplyProduct::where('category', $c->name)->count();

            return $c;
        });

        return view('portal.hq.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('product_categories', 'name')],
            'code' => ['nullable', 'string', 'max:10', 'alpha_num', Rule::unique('product_categories', 'code')],
            'sort_order' => ['nullable', 'integer'],
        ]);

        ProductCategory::create([
            'name' => $data['name'],
            'code' => strtoupper($data['code'] ?? $this->genCode($data['name'])),
            'sort_order' => $data['sort_order'] ?? (int) ProductCategory::max('sort_order') + 1,
        ]);

        return back()->with('success', '카테고리를 추가했습니다.');
    }

    public function update(Request $request, ProductCategory $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('product_categories', 'name')->ignore($category->id)],
            'code' => ['required', 'string', 'max:10', 'alpha_num', Rule::unique('product_categories', 'code')->ignore($category->id)],
            'sort_order' => ['nullable', 'integer'],
        ]);
        $data['code'] = strtoupper($data['code']);

        DB::transaction(function () use ($category, $data) {
            // 소속 품목의 분류명/코드 동기화
            SupplyProduct::where('category', $category->name)->update([
                'category' => $data['name'],
                'category_code' => $data['code'],
            ]);
            $category->update([
                'name' => $data['name'],
                'code' => $data['code'],
                'sort_order' => $data['sort_order'] ?? $category->sort_order,
            ]);
        });

        return back()->with('success', '카테고리를 수정했습니다. (소속 품목 분류도 함께 갱신)');
    }

    public function destroy(ProductCategory $category)
    {
        $count = SupplyProduct::where('category', $category->name)->count();
        if ($count > 0) {
            return back()->withErrors(['category' => "이 카테고리에 품목 {$count}개가 있어 삭제할 수 없습니다. 품목을 옮긴 뒤 삭제하세요."]);
        }
        $category->delete();

        return back()->with('success', '카테고리를 삭제했습니다.');
    }

    private function genCode(string $name): string
    {
        $ascii = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
        $code = strlen($ascii) >= 2 ? substr($ascii, 0, 3) : 'CAT'.((int) ProductCategory::max('id') + 1);
        $base = $code;
        $i = 1;
        while (ProductCategory::where('code', $code)->exists()) {
            $code = $base.$i++;
        }

        return $code;
    }
}
