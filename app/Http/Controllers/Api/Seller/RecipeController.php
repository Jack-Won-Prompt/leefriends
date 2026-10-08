<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\SupplyProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 레시피 관리 — 본사 전용 (목록/등록/삭제, 앱).
 * 웹 포털 «레시피»(Portal\Hq\RecipeController)와 같은 조건.
 * 이미지는 웹과 동일하게 public/images/recipes 에 저장해 웹·앱이 공유한다.
 */
class RecipeController extends Controller
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
        $recipes = Recipe::with(['product', 'author'])->sorted()->paginate(20);

        return response()->json([
            'data' => $recipes->getCollection()->map(fn (Recipe $r) => [
                'id' => $r->id,
                'title' => $r->title,
                'product_name' => $r->product_name ?: (optional($r->product)->name),
                'content' => $r->content,
                'image_url' => $r->image_url,
                'author' => $r->author?->name,
                'created_at' => $r->created_at?->format('Y-m-d H:i'),
            ])->values(),
            'meta' => [
                'current_page' => $recipes->currentPage(),
                'last_page' => $recipes->lastPage(),
                'total' => $recipes->total(),
                'products' => SupplyProduct::active()->approved()->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureHq($request);
        $data = $request->validate([
            'supply_product_id' => ['nullable', 'integer', 'exists:supply_products,id'],
            'title' => ['required', 'string', 'max:150'],
            'content' => ['nullable', 'string', 'max:50000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'title.required' => '레시피 제목을 입력해 주세요.',
            'image.mimes' => '이미지는 jpg, png, webp 형식만 업로드할 수 있습니다.',
            'image.max' => '이미지 용량은 5MB 이하만 가능합니다.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = 'recipe_'.time().'.'.strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $file->move(public_path('images/recipes'), $filename);
            $imagePath = 'images/recipes/'.$filename;
        }

        $productName = null;
        if (! empty($data['supply_product_id'])) {
            $productName = optional(SupplyProduct::find($data['supply_product_id']))->name;
        }

        $recipe = Recipe::create([
            'supply_product_id' => $data['supply_product_id'] ?? null,
            'product_name' => $productName,
            'title' => $data['title'],
            'content' => $this->plainTextToHtml($data['content'] ?? null),
            'image' => $imagePath,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['message' => '레시피를 등록했습니다.', 'id' => $recipe->id], 201);
    }

    public function destroy(Request $request, Recipe $recipe): JsonResponse
    {
        $this->ensureHq($request);
        $recipe->delete();

        return response()->json(['message' => '레시피를 삭제했습니다.']);
    }

    /**
     * 앱에서 넘어온 일반 텍스트를 안전한 HTML로 변환한다.
     * 이미 HTML 태그가 있으면 그대로 두고, 평문이면 문단/줄바꿈을 보존한다.
     */
    private function plainTextToHtml(?string $text): ?string
    {
        $text = trim((string) $text);
        if ($text === '') {
            return null;
        }
        // 이미 HTML이면 그대로 저장 (웹 Quill 경로와의 호환)
        if ($text !== strip_tags($text)) {
            return $text;
        }
        $paragraphs = preg_split("/\n{2,}/", $text);

        return collect($paragraphs)
            ->map(fn ($p) => '<p>'.nl2br(e(trim($p))).'</p>')
            ->implode('');
    }
}
