<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\SupplyProduct;
use App\Support\RecipeMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 레시피 관리 — 본사 전용 (목록/등록/수정/삭제, 앱).
 * 웹 포털 «레시피»(Portal\Hq\RecipeController)와 같은 조건.
 * 이미지·영상은 웹과 동일하게 public/images/recipes, public/videos/recipes 에 저장해 웹·앱이 공유한다.
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
            'data' => $recipes->getCollection()->map(fn (Recipe $r) => $this->present($r))->values(),
            'meta' => [
                'current_page' => $recipes->currentPage(),
                'last_page' => $recipes->lastPage(),
                'total' => $recipes->total(),
                'video_max_mb' => RecipeMedia::maxUploadMb(),
                'products' => SupplyProduct::active()->approved()->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name])->values(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureHq($request);
        $data = $this->validated($request);

        $productName = null;
        if (! empty($data['supply_product_id'])) {
            $productName = optional(SupplyProduct::find($data['supply_product_id']))->name;
        }

        $recipe = Recipe::create([
            'supply_product_id' => $data['supply_product_id'] ?? null,
            'product_name' => $productName,
            'title' => $data['title'],
            'content' => $this->plainTextToHtml($data['content'] ?? null),
            'image' => $request->hasFile('image') ? RecipeMedia::storeImage($request->file('image')) : null,
            'video' => $request->hasFile('video') ? RecipeMedia::storeVideo($request->file('video')) : null,
            'video_link' => $data['video_link'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => '레시피를 등록했습니다.',
            'id' => $recipe->id,
            'data' => $this->present($recipe->fresh(['product', 'author'])),
        ], 201);
    }

    /** POST(_method=PUT)/PUT /api/v1/seller/recipes/{recipe} */
    public function update(Request $request, Recipe $recipe): JsonResponse
    {
        $this->ensureHq($request);
        $data = $this->validated($request);

        $recipe->title = $data['title'];
        $recipe->content = $this->plainTextToHtml($data['content'] ?? null);
        $recipe->supply_product_id = $data['supply_product_id'] ?? null;
        $recipe->product_name = ! empty($data['supply_product_id'])
            ? optional(SupplyProduct::find($data['supply_product_id']))->name
            : null;
        $recipe->video_link = $data['video_link'] ?? null;

        if ($request->hasFile('image')) {
            RecipeMedia::delete($recipe->image);
            $recipe->image = RecipeMedia::storeImage($request->file('image'));
        } elseif ($request->boolean('remove_image')) {
            RecipeMedia::delete($recipe->image);
            $recipe->image = null;
        }

        if ($request->hasFile('video')) {
            RecipeMedia::delete($recipe->video);
            $recipe->video = RecipeMedia::storeVideo($request->file('video'));
        } elseif ($request->boolean('remove_video')) {
            RecipeMedia::delete($recipe->video);
            $recipe->video = null;
        }

        $recipe->save();

        return response()->json([
            'message' => '레시피를 수정했습니다.',
            'data' => $this->present($recipe->fresh(['product', 'author'])),
        ]);
    }

    public function destroy(Request $request, Recipe $recipe): JsonResponse
    {
        $this->ensureHq($request);
        RecipeMedia::delete($recipe->image);
        RecipeMedia::delete($recipe->video);
        $recipe->delete();

        return response()->json(['message' => '레시피를 삭제했습니다.']);
    }

    /** 등록·수정이 같은 규칙을 쓰도록 검증을 한곳에 모았다. */
    private function validated(Request $request): array
    {
        $videoMaxKb = RecipeMedia::maxUploadMb() * 1024;

        return $request->validate([
            'supply_product_id' => ['nullable', 'integer', 'exists:supply_products,id'],
            'title' => ['required', 'string', 'max:150'],
            'content' => ['nullable', 'string', 'max:50000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'video' => ['nullable', 'file', 'mimes:'.RecipeMedia::VIDEO_EXTENSIONS, 'max:'.$videoMaxKb],
            'video_link' => ['nullable', 'string', 'max:500', 'url'],
        ], [
            'title.required' => '레시피 제목을 입력해 주세요.',
            'image.mimes' => '이미지는 jpg, png, webp 형식만 업로드할 수 있습니다.',
            'image.max' => '이미지 용량은 5MB 이하만 가능합니다.',
            'video.mimes' => '영상은 mp4, webm, mov, m4v 형식만 업로드할 수 있습니다.',
            'video.max' => '영상 용량은 약 '.RecipeMedia::maxUploadMb().'MB 이하만 가능합니다. 더 큰 영상은 video_link(영상 링크)를 사용하세요.',
            'video_link.url' => '영상 링크는 http로 시작하는 주소여야 합니다.',
        ]);
    }

    private function present(Recipe $r): array
    {
        return [
            'id' => $r->id,
            'title' => $r->title,
            'supply_product_id' => $r->supply_product_id,
            'product_name' => $r->product_name ?: (optional($r->product)->name),
            'content' => $r->content,
            'image_url' => $r->image_url,
            'video_url' => $r->video_url,
            'video_link' => $r->video_link ?: null,
            'video_embed_url' => $r->video_embed_url,
            'has_video' => $r->has_video,
            'author' => $r->author?->name,
            'created_at' => $r->created_at?->format('Y-m-d H:i'),
        ];
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
