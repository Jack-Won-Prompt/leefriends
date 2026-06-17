<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 매장찾기 (공개).
 */
class StoreLocatorController extends Controller
{
    /**
     * GET /api/v1/stores?region=all|<지역>&q=<검색어>
     */
    public function index(Request $request): JsonResponse
    {
        $region = $request->query('region', 'all');
        $keyword = trim((string) $request->query('q', ''));

        $query = Store::active()->orderBy('region')->orderBy('name');
        if ($region !== 'all') {
            $query->where('region', $region);
        }
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('address', 'like', "%{$keyword}%");
            });
        }

        $stores = $query->get()->map(fn (Store $s) => [
            'id' => $s->id,
            'name' => $s->name,
            'region' => $s->region,
            'address' => trim("{$s->address} {$s->address_detail}"),
            'phone' => $s->phone,
            'hours' => $s->hours,
            'lat' => $s->lat,
            'lng' => $s->lng,
            'image' => $s->image ? asset($s->image) : null,
        ]);

        $regions = Store::active()->select('region')->distinct()->pluck('region')->values();

        return response()->json([
            'data' => $stores->values(),
            'meta' => ['region' => $region, 'regions' => $regions],
        ]);
    }
}
