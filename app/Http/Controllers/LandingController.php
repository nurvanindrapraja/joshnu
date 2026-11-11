<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Issue;
use App\Models\JournalSetting;
use App\Models\Page;
use App\Models\Publication;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        $setting = JournalSetting::current();

        $announcements = Announcement::active()->latest('starts_at')->limit(3)->get();
        $currentIssue = Issue::published()->first();
        $latestArticles = Publication::published()
            ->with(['metadata', 'issue'])
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();

        $pages = Page::where('is_active', true)->orderBy('title')->get();

        return view('public.home', compact('setting', 'announcements', 'currentIssue', 'latestArticles', 'pages'));
    }
}
