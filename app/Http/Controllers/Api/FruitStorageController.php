<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FruitStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 매장용 과일 보관 가이드 (본사가 공유한 항목, 읽기 전용).
 */
class FruitStorageController extends Controller
{
    /** GET /api/v1/fruit-storages?q= */
    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $rows = FruitStorage::active()->shared()
            ->when($q !== '', fn ($x) => $x->where('name', 'like', "%{$q}%"))
            ->orderBy('sort_order')->orderBy('name')
            ->get()
            ->map(fn (FruitStorage $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'temp_c' => $f->temp_c,
                'temp_f' => $f->temp_f,
                'ventilation' => $f->ventilation,
                'humidity' => $f->humidity,
                'dehumidification' => $f->dehumidification,
                'storage_period' => $f->storage_period,
                'note' => $f->note,
            ]);

        return response()->json(['data' => $rows->values()]);
    }
}
