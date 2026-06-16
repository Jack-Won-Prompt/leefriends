<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;

/**
 * 재료 마스터 — «추가 품목 재료», «기타 재료» (품목과 별개).
 */
class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $extras = Material::ofType('extra')->orderBy('sort_order')->orderBy('id')->get();
        $etcs = Material::ofType('etc')->orderBy('sort_order')->orderBy('id')->get();

        return view('portal.hq.materials.index', compact('extras', 'etcs'));
    }

    public function store(Request $request)
    {
        Material::create($this->validateData($request));

        return back()->with('success', '재료가 등록되었습니다.');
    }

    public function update(Request $request, Material $material)
    {
        $material->update($this->validateData($request));

        return back()->with('success', '재료가 수정되었습니다.');
    }

    public function destroy(Material $material)
    {
        $material->delete();

        return back()->with('success', '재료가 삭제되었습니다.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', 'in:extra,etc'],
            'name' => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'unit' => ['required', 'string', 'max:30'],
            'spec' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
