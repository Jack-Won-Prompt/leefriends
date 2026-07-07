<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\FruitStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 본사 과일 보관 관리 — 냉장/냉동 가이드라인 CRUD + 매장 공유 토글. 본사 전용.
 */
class FruitStorageController extends Controller
{
    use ResolvesSeller;

    /** GET /api/v1/seller/fruit-storages?q= */
    public function index(Request $request): JsonResponse
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403);

        $q = trim((string) $request->query('q', ''));
        $rows = FruitStorage::query()
            ->when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('sort_order')->orderBy('name')
            ->get()
            ->map(fn (FruitStorage $f) => $this->row($f));

        return response()->json(['data' => $rows->values()]);
    }

    /** POST /api/v1/seller/fruit-storages */
    public function store(Request $request): JsonResponse
    {
        $this->hq($request);
        $f = FruitStorage::create($this->validated($request));

        return response()->json(['message' => '보관 항목이 등록되었습니다.', 'data' => $this->row($f)], 201);
    }

    /** PUT /api/v1/seller/fruit-storages/{fruit} */
    public function update(Request $request, FruitStorage $fruit): JsonResponse
    {
        $this->hq($request);
        $fruit->update($this->validated($request));

        return response()->json(['message' => '보관 항목이 수정되었습니다.', 'data' => $this->row($fruit->fresh())]);
    }

    /** PATCH /api/v1/seller/fruit-storages/{fruit}/toggle-share */
    public function toggleShare(Request $request, FruitStorage $fruit): JsonResponse
    {
        $this->hq($request);
        $fruit->update(['is_shared' => ! $fruit->is_shared]);

        return response()->json([
            'message' => $fruit->is_shared ? '매장 공유를 켰습니다.' : '매장 공유를 껐습니다.',
            'data' => $this->row($fruit),
        ]);
    }

    /** DELETE /api/v1/seller/fruit-storages/{fruit} */
    public function destroy(Request $request, FruitStorage $fruit): JsonResponse
    {
        $this->hq($request);
        $fruit->delete();

        return response()->json(['message' => '보관 항목이 삭제되었습니다.']);
    }

    private function hq(Request $request): void
    {
        [$type] = $this->seller($request);
        abort_unless($type === 'hq', 403);
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
        $data['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    private function row(FruitStorage $f): array
    {
        return [
            'id' => $f->id,
            'name' => $f->name,
            'temp_c' => $f->temp_c,
            'temp_f' => $f->temp_f,
            'ventilation' => $f->ventilation,
            'humidity' => $f->humidity,
            'dehumidification' => $f->dehumidification,
            'storage_period' => $f->storage_period,
            'note' => $f->note,
            'is_shared' => (bool) $f->is_shared,
            'is_active' => (bool) $f->is_active,
            'sort_order' => (int) $f->sort_order,
        ];
    }
}
