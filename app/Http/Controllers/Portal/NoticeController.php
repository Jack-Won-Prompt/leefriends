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
        return view('portal.notices.index', [
            'notices' => PortalNotice::visibleTo(Auth::user())->sorted()->paginate(15),
        ]);
    }

    public function show(PortalNotice $notice)
    {
        abort_unless($notice->isVisibleTo(Auth::user()), 403);

        return view('portal.notices.show', ['notice' => $notice]);
    }
}
