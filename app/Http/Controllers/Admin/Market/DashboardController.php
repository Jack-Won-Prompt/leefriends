<?php

namespace App\Http\Controllers\Admin\Market;

use App\Market\Models\DataSource;
use App\Market\Models\Region;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends \App\Http\Controllers\Controller
{
    public function index(Request $request): View
    {
        $user = \App\Market\Models\User::marketOwner();
        $analyses = $user->analyses()->limit(6)->get();

        return view('market.dashboard', [
            'analyses' => $analyses,
            'totalAnalyses' => $user->analyses()->count(),
            'completedAnalyses' => $user->analyses()->where('status', 'completed')->count(),
            'favorites' => $user->favoriteRegions()->with('region')->limit(8)->get(),
            'regionCount' => Region::count(),
            'sources' => DataSource::orderBy('sort_order')->get(),
        ]);
    }
}
