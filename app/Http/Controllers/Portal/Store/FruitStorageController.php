<?php

namespace App\Http\Controllers\Portal\Store;

use App\Http\Controllers\Controller;
use App\Models\FruitStorage;
use Illuminate\Http\Request;

/** 매장 포털 — 본사가 공유한 과일 보관 가이드 (읽기 전용) */
class FruitStorageController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $fruits = FruitStorage::active()->shared()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(30)->withQueryString();

        return view('portal.store.fruit_storages.index', compact('fruits', 'q'));
    }
}
