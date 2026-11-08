<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use App\Http\Concerns\RespondsToAjax;
use App\Services\SubmissionWorkflow;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DecisionController extends Controller
{
    use RespondsToAjax;

    public function __construct(private SubmissionWorkflow $workflow)
    {
    }

    public function store(Request $request, Submission $submission): Response
    {
        abort_unless($request->user()->isEditorial(), 403);

        $data = $request->validate([
            'type' => ['required', 'in:accept,minor_revise,major_revise,reject,continue_review'],
            'comment_to_author' => ['required', 'string', 'max:20000'],
            'internal_note' => ['nullable', 'string', 'max:5000'],
            'revision_due_days' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $decision = $this->workflow->decide($submission, $request->user(), $data);

        return $this->ajaxOrRedirect(
            $request,
            'Keputusan "'.\App\Models\Decision::TYPE_LABELS[$decision->type].'" tersimpan dan dikirim ke penulis.',
            redirect()->route('editor.submissions.show', $submission)
        );
    }
}
