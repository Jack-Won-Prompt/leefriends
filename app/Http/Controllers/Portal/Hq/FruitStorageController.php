<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\FruitStorage;
use Illuminate\Http\Request;

/** 본사 포털 — 과일 보관 관리 (냉장/냉동 가이드라인, 매장 공유) */
class FruitStorageController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $fruits = FruitStorage::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(30)->withQueryString();

        return view('portal.hq.fruit_storages.index', compact('fruits', 'q'));
    }

    public function store(Request $request)
    {
        FruitStorage::create($this->validated($request));

        return back()->with('success', '보관 항목이 등록되었습니다.');
    }

    public function update(Request $request, FruitStorage $fruit)
    {
        $fruit->update($this->validated($request));

        return back()->with('success', '보관 항목이 수정되었습니다.');
    }

    /** 매장 공유 토글 (체크박스 즉시 반영) */
    public function toggleShare(FruitStorage $fruit)
    {
        $fruit->update(['is_shared' => ! $fruit->is_shared]);

        return back()->with('success', $fruit->is_shared ? "‘{$fruit->name}’ 매장 공유를 켰습니다." : "‘{$fruit->name}’ 매장 공유를 껐습니다.");
    }

    public function destroy(FruitStorage $fruit)
    {
        $fruit->delete();

        return back()->with('success', '보관 항목이 삭제되었습니다.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'temp_c' => ['nullable', 'string', 'max:50'],
            'temp_f' => ['nullable', 'string', 'max:50'],
            'ventilation' => ['nullable', 'string', 'max:50'],
            'humidity' => ['nullable', 'string', 'max:50'],
            'dehumidification' => ['nullable', 'string', 'max:20'],
            'storage_period' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_shared' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_shared'] = $request->boolean('is_shared');
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
