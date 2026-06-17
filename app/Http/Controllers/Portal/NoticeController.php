<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalNotice;
use Illuminate\Support\Facades\Auth;

/**
 * 매장/공급처 — 본사 공지사항 열람.
 */
class NoticeController extends Controller
{
    public function index()
    {
        $role = Auth::user()->role;

        return view('portal.notices.index', [
            'notices' => PortalNotice::forRole($role)->sorted()->paginate(15),
        ]);
    }

    public function show(PortalNotice $notice)
    {
        $role = Auth::user()->role;
        abort_unless(in_array($notice->audience, ['all', $role], true), 403);

        return view('portal.notices.show', ['notice' => $notice]);
    }
}
