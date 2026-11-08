<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Section;
use App\Models\Submission;
use App\Http\Concerns\RespondsToAjax;
use App\Services\SubmissionWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class EditorSubmissionController extends Controller
{
    use RespondsToAjax;

    public function __construct(private SubmissionWorkflow $workflow)
    {
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['stage', 'section', 'author', 'q', 'date_from', 'date_to']);

        $query = Submission::with(['section', 'author']);

        if (! empty($filters['stage'])) {
            $query->where('stage', $filters['stage']);
        }
        if (! empty($filters['section'])) {
            $query->where('section_id', $filters['section']);
        }
        if (! empty($filters['author'])) {
            $query->whereHas('author', fn ($a) => $a->where('name', 'like', '%'.$filters['author'].'%'));
        }
        if (! empty($filters['q'])) {
            $like = '%'.$filters['q'].'%';
            $query->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('reference_no', 'like', $like));
        }
        if (! empty($filters['date_from'])) {
            $query->where('submitted_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('submitted_at', '<=', $filters['date_to']);
        }

        $submissions = $query->latest('submitted_at')->paginate(20)->withQueryString();

        return view('editor.submissions.index', [
            'submissions' => $submissions,
            'filters' => $filters,
            'sections' => Section::orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Submission $submission): View
    {
        $this->authorize($request, $submission);
        $submission->load([
            'section', 'author', 'authors', 'revisions', 'files',
            'reviews.reviewer', 'decisions.editor', 'events.actor',
            'assignments.user', 'payment', 'publication', 'messages',
        ]);

        return view('editor.submissions.show', compact('submission'));
    }

    public function transitionStage(Request $request, Submission $submission): Response
    {
        $this->authorize($request, $submission);

        $data = $request->validate([
            'to_stage' => ['required', 'string', 'in:under_review,decision,rejected,withdrawn'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->workflow->transition($submission, $data['to_stage'], $request->user(), ['note' => $data['note'] ?? null]);

        return $this->ajaxOrRedirect($request, 'Stage naskah diubah menjadi '.$submission->stageLabel().'.', back());
    }

    public function downloadFile(Request $request, Submission $submission, \App\Models\SubmissionFile $file)
    {
        $this->authorize($request, $submission);
        abort_unless($file->submission_id === $submission->id, 404);

        AuditLog::log($request->user()->id, 'submission.file_download', 'Submission', $submission->id, ['file_id' => $file->id]);

        return response()->download(Storage::disk('local')->path($file->path), $file->original_name);
    }

    public function audit(Request $request, Submission $submission): View
    {
        $this->authorize($request, $submission);
        $submission->load('events.actor');

        return view('editor.submissions.audit', compact('submission'));
    }

    private function authorize(Request $request, Submission $submission): void
    {
        abort_unless($request->user()->isEditorial() || $request->user()->isProduction(), 403);
    }
}
