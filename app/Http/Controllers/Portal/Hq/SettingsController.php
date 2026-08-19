<?php

namespace App\Http\Controllers\Portal\Hq;

use App\Http\Controllers\Controller;
use App\Models\MenuSetting;
use App\Support\PortalMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    /** 항상 표시(숨김 불가)인 메뉴 */
    private const LOCKED = ['portal.dashboard', 'portal.hq.settings.index'];

    public function index()
    {
        $role = Auth::user()->role ?: 'hq';
        $hidden = MenuSetting::hiddenRoutes();

        $rows = collect(PortalMenu::flat($role))->map(fn ($m) => array_merge($m, [
            'hidden' => in_array($m['route'], $hidden, true),
            'locked' => in_array($m['route'], self::LOCKED, true),
        ]))->values();

        return view('portal.hq.settings.index', ['rows' => $rows]);
    }

    /** 메뉴 표시/숨김 토글 (AJAX) */
    public function toggle(Request $request)
    {
        $data = $request->validate([
            'route' => ['required', 'string', 'max:120'],
            'hidden' => ['required', 'boolean'],
        ]);

        abort_if(in_array($data['route'], self::LOCKED, true), 422, '이 메뉴는 숨길 수 없습니다.');

        MenuSetting::updateOrCreate(
            ['route' => $data['route']],
            ['hidden' => (bool) $data['hidden']],
        );

        return response()->json(['ok' => true, 'route' => $data['route'], 'hidden' => (bool) $data['hidden']]);
    }
}
