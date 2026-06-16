<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = AppNotification::where('user_id', Auth::id())->latest()->paginate(20);

        return view('portal.notifications.index', compact('notifications'));
    }

    public function read(AppNotification $notification)
    {
        abort_unless($notification->user_id === Auth::id(), 403);
        $notification->update(['read_at' => now()]);

        return back();
    }

    public function readAll()
    {
        AppNotification::where('user_id', Auth::id())->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', '모든 알림을 읽음 처리했습니다.');
    }
}
