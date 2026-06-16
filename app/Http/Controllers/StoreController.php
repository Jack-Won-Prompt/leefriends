<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $request)
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
        $stores = $query->get();

        $regions = Store::active()->select('region')->distinct()->pluck('region');

        return view('store', compact('stores', 'regions', 'region', 'keyword'));
    }
}
