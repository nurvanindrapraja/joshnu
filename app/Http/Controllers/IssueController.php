<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Services\PdfService;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IssueController extends Controller
{
    public function index(): View
    {
        $issues = Issue::published()->with('publishedPublications.metadata')->get()
            ->groupBy(fn (Issue $i) => $i->year.'-'.$i->volume);

        $currentIssue = Issue::published()->first();

        return view('public.archive', compact('issues', 'currentIssue'));
    }

    public function current(): View|StreamedResponse
    {
        $issue = Issue::published()->first();
        abort_if(! $issue, 404, 'Belum ada issue terbit.');

        return $this->show($issue);
    }

    public function show(Issue $issue): View
    {
        abort_unless($issue->isPublished(), 404);

        $issue->load('publishedPublications.metadata');
        $issuePdf = Storage::disk('local')->exists('issues/'.$issue->id.'/issue.pdf')
            ? route('issues.pdf', $issue->slug)
            : null;

        return view('public.issue', compact('issue', 'issuePdf'));
    }

    public function pdf(Issue $issue, PdfService $pdf): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        abort_unless($issue->isPublished(), 404);

        $path = 'issues/'.$issue->id.'/issue.pdf';
        if (! Storage::disk('local')->exists($path)) {
            $path = $pdf->generateIssuePdf($issue);
        }

        return response()->download(Storage::disk('local')->path($path), 'joshnu-'.$issue->slug.'.pdf');
    }
}
