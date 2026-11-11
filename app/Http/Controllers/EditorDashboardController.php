<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Payment;
use App\Models\Section;
use App\Models\Subscriber;
use App\Models\Submission;
use App\Models\User;
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

        // Ringkasan: penulis & subscriber
        $stats = [
            'manuscripts_total' => (int) Submission::count(),
            'authors_submitted' => (int) Submission::query()->distinct()->count('author_id'),
            // "author" = user tanpa role apa pun atau hanya role author (sama dengan tab Penulis)
            'authors_registered' => (int) User::whereDoesntHave('roles', fn ($r) => $r->where('role', '!=', 'author'))->count(),
            'subscribers' => (int) Subscriber::count(),
        ];

        // Rekap keuangan APC: verified / pending / rejected
        $finance = [
            'verified' => ['count' => 0, 'amount' => 0.0],
            'pending' => ['count' => 0, 'amount' => 0.0],
            'rejected' => ['count' => 0, 'amount' => 0.0],
        ];
        Payment::selectRaw('status, count(*) as total, coalesce(sum(amount), 0) as amount')->groupBy('status')->get()
            ->each(function ($row) use (&$finance) {
                $bucket = $row->status === 'verified' ? 'verified' : ($row->status === 'rejected' ? 'rejected' : 'pending');
                $finance[$bucket]['count'] += (int) $row->total;
                $finance[$bucket]['amount'] += (float) $row->amount;
            });

        // Page terbaru (untuk ringkasan dashboard)
        $latestPages = Page::latest('updated_at')->limit(5)->get();

        return view('editor.dashboard', compact('counts', 'slaAlerts', 'queue', 'activeStage', 'myMessages', 'stats', 'finance', 'latestPages', 'user'));
    }
}
