<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\SupplyProduct;
use App\Support\RecipeMedia;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * 본사 — 물품별 레시피 등록·수정·관리 (이미지 + 영상 + 글).
 */
class RecipeController extends Controller
{
    public function index()
    {
        return view('portal.hq.recipes.index', [
            'recipes' => Recipe::with(['product', 'author'])->sorted()->paginate(20),
            'products' => SupplyProduct::active()->approved()->orderBy('name')->get(['id', 'name']),
            'videoMaxMb' => RecipeMedia::maxUploadMb(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $productName = null;
        if (! empty($data['supply_product_id'])) {
            $productName = optional(SupplyProduct::find($data['supply_product_id']))->name;
        }

        try {
            $recipe = Recipe::create([
                'supply_product_id' => $data['supply_product_id'] ?? null,
                'product_name' => $productName,
                'title' => $data['title'],
                'content' => $this->normalizeQuillHtml($data['content'] ?? null),
                'image' => $request->hasFile('image_file') ? RecipeMedia::storeImage($request->file('image_file')) : null,
                'video' => $request->hasFile('video_file') ? RecipeMedia::storeVideo($request->file('video_file')) : null,
                'video_link' => $data['video_link'] ?? null,
                'created_by' => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            return $this->saveFailed($e);
        }

        return redirect()->route('portal.hq.recipes.index')
            ->with('success', '레시피를 등록했습니다.'.($recipe->has_video ? ' 영상도 함께 저장했습니다.' : ''));
    }

    public function update(Request $request, Recipe $recipe)
    {
        $data = $this->validated($request);

        $recipe->title = $data['title'];
        $recipe->content = $this->normalizeQuillHtml($data['content'] ?? null);
        $recipe->supply_product_id = $data['supply_product_id'] ?? null;
        $recipe->product_name = ! empty($data['supply_product_id'])
            ? optional(SupplyProduct::find($data['supply_product_id']))->name
            : null;
        $recipe->video_link = $data['video_link'] ?? null;

        try {
            // 이미지: 새로 올리면 교체, «삭제» 체크하면 지움, 아니면 그대로
            if ($request->hasFile('image_file')) {
                $new = RecipeMedia::storeImage($request->file('image_file'));
                RecipeMedia::delete($recipe->image);
                $recipe->image = $new;
            } elseif ($request->boolean('remove_image')) {
                RecipeMedia::delete($recipe->image);
                $recipe->image = null;
            }

            // 영상: 이미지와 같은 규칙 — 새 업로드 > 삭제 체크 > 유지
            if ($request->hasFile('video_file')) {
                $new = RecipeMedia::storeVideo($request->file('video_file'));
                RecipeMedia::delete($recipe->video);
                $recipe->video = $new;
            } elseif ($request->boolean('remove_video')) {
                RecipeMedia::delete($recipe->video);
                $recipe->video = null;
            }

            $recipe->save();
        } catch (\Throwable $e) {
            return $this->saveFailed($e);
        }

        return redirect()->route('portal.hq.recipes.index')->with('success', '레시피를 수정했습니다.');
    }

    public function destroy(Recipe $recipe)
    {
        RecipeMedia::delete($recipe->image);
        RecipeMedia::delete($recipe->video);
        $recipe->delete();

        return redirect()->route('portal.hq.recipes.index')->with('success', '레시피를 삭제했습니다.');
    }

    /** 등록·수정이 같은 규칙을 쓰도록 검증을 한곳에 모았다. */
    private function validated(Request $request): array
    {
        $videoMaxKb = RecipeMedia::maxUploadMb() * 1024;

        return $request->validate([
            'supply_product_id' => ['nullable', 'integer', 'exists:supply_products,id'],
            'title' => ['required', 'string', 'max:150'],
            'content' => ['nullable', 'string', 'max:50000'],
            'image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'video_file' => ['nullable', 'file', 'mimes:'.RecipeMedia::VIDEO_EXTENSIONS, 'max:'.$videoMaxKb],
            'video_link' => ['nullable', 'string', 'max:500', 'url'],
        ], [
            'title.required' => '레시피 제목을 입력해 주세요.',
            'image_file.mimes' => '이미지는 jpg, png, webp 형식만 업로드할 수 있습니다.',
            'image_file.max' => '이미지 용량은 5MB 이하만 가능합니다.',
            'video_file.mimes' => '영상은 mp4, webm, mov, m4v 형식만 업로드할 수 있습니다.',
            'video_file.max' => "영상 용량은 {$videoMaxKb}KB(약 ".RecipeMedia::maxUploadMb().'MB) 이하만 가능합니다. 더 큰 영상은 «영상 링크»를 사용하세요.',
            'video_link.url' => '영상 링크는 http로 시작하는 주소여야 합니다.',
        ]);
    }

    /**
     * Quill 2.x가 내보내는 에디터 전용 마크업을 표준 HTML로 정규화한다.
     * - <span class="ql-ui"> 편집 전용 마커 제거
     * - 리스트: Quill은 모든 항목을 <ol><li data-list="bullet|ordered">로 묶으므로
     *   data-list 종류에 따라 표준 <ul>/<ol>로 분리해 웹·앱 어디서나 렌더되게 한다.
     */
    private function normalizeQuillHtml(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '' || $html === '<p><br></p>') {
            return null;
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // 1) ql-ui 편집 전용 마커 제거
        foreach (iterator_to_array($xpath->query('//span[contains(@class, "ql-ui")]')) as $node) {
            $node->parentNode->removeChild($node);
        }

        // 2) Quill 리스트(<ol> 내부 data-list 항목) → 표준 <ul>/<ol>로 분리
        foreach (iterator_to_array($xpath->query('//ol[li[@data-list]]')) as $ol) {
            $items = [];
            foreach (iterator_to_array($ol->childNodes) as $child) {
                if ($child->nodeName === 'li') {
                    $items[] = $child;
                }
            }
            $parent = $ol->parentNode;
            $currentList = null;
            $currentType = null;
            foreach ($items as $li) {
                $type = $li->getAttribute('data-list') === 'ordered' ? 'ol' : 'ul';
                $li->removeAttribute('data-list');
                if ($currentType !== $type) {
                    $currentList = $dom->createElement($type);
                    $parent->insertBefore($currentList, $ol);
                    $currentType = $type;
                }
                $currentList->appendChild($li);
            }
            $parent->removeChild($ol);
        }

        $root = $dom->getElementById('__root');
        $out = '';
        if ($root) {
            foreach ($root->childNodes as $child) {
                $out .= $dom->saveHTML($child);
            }
        }

        return trim($out) ?: null;
    }
}
