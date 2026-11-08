<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::active()->latest('starts_at')->paginate(20);

        return view('public.announcements', compact('announcements'));
    }

    public function show(Announcement $announcement): View
    {
        abort_unless($announcement->is_active, 404);

        return view('public.announcement', compact('announcement'));
    }
}
