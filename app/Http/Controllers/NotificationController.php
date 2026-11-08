<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()->notifications()->latest('created_at')->limit(50)->get();

        return view('account.notifications', compact('notifications'));
    }

    public function markRead(Request $request): RedirectResponse
    {
        $ids = $request->input('ids') ?? [];

        $request->user()->notifications()
            ->whereNull('read_at')
            ->when(is_array($ids) && count($ids), fn ($q) => $q->whereIn('id', $ids))
            ->update(['read_at' => now()]);

        return back()->with('status', 'Notifikasi ditandai dibaca.');
    }
}
