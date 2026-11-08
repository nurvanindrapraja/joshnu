<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EditorDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $stages = ['submitted', 'under_review', 'decision', 'revised', 'accepted', 'scheduled'];
        $counts = [];
        foreach ($stages as $stage) {
            $counts[$stage] = Submission::stage($stage)->count();
        }

        $slaAlerts = Submission::whereIn('stage', ['submitted', 'under_review', 'decision'])
            ->get()
            ->filter(fn (Submission $s) => $s->daysInStage() >= 30)
            ->sortByDesc(fn (Submission $s) => $s->daysInStage())
            ->values();

        $activeStage = in_array($request->query('stage'), $stages) ? $request->query('stage') : 'submitted';
        $queue = Submission::stage($activeStage)
            ->with(['section', 'author', 'reviews'])
            ->latest('submitted_at')
            ->limit(15)
            ->get();

        $myMessages = \App\Models\Message::where(fn ($q) => $q->where('is_internal', true)
            ->orWhere('recipient_id', $user->id))->latest('created_at')->limit(5)->get();

        return view('editor.dashboard', compact('counts', 'slaAlerts', 'queue', 'activeStage', 'myMessages'));
    }
}
