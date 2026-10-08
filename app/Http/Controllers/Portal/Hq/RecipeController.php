<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\SupplyProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 본사 — 물품별 레시피 등록·관리 (이미지 + 글).
 */
class RecipeController extends Controller
{
    public function index()
    {
        return view('portal.hq.recipes.index', [
            'recipes' => Recipe::with(['product', 'author'])->sorted()->paginate(20),
            'products' => SupplyProduct::active()->approved()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supply_product_id' => ['nullable', 'integer', 'exists:supply_products,id'],
            'title' => ['required', 'string', 'max:150'],
            'content' => ['nullable', 'string', 'max:50000'],
            'image_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'title.required' => '레시피 제목을 입력해 주세요.',
            'image_file.mimes' => '이미지는 jpg, png, webp 형식만 업로드할 수 있습니다.',
            'image_file.max' => '이미지 용량은 5MB 이하만 가능합니다.',
        ]);

        $image = null;
        if ($request->hasFile('image_file')) {
            $file = $request->file('image_file');
            $filename = 'recipe_'.time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('images/recipes'), $filename);
            $image = 'images/recipes/'.$filename;
        }

        $productName = null;
        if (! empty($data['supply_product_id'])) {
            $productName = optional(SupplyProduct::find($data['supply_product_id']))->name;
        }

        Recipe::create([
            'supply_product_id' => $data['supply_product_id'] ?? null,
            'product_name' => $productName,
            'title' => $data['title'],
            'content' => $this->normalizeQuillHtml($data['content'] ?? null),
            'image' => $image,
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('portal.hq.recipes.index')->with('success', '레시피를 등록했습니다.');
    }

    public function destroy(Recipe $recipe)
    {
        $recipe->delete();

        return redirect()->route('portal.hq.recipes.index')->with('success', '레시피를 삭제했습니다.');
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
