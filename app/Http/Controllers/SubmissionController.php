<?php

namespace App\Http\Controllers;

use App\Events\SubmissionSubmitted;
use App\Models\AuditLog;
use App\Models\Section;
use App\Models\Submission;
use App\Models\SubmissionAuthor;
use App\Models\SubmissionEvent;
use App\Models\SubmissionFile;
use App\Models\SubmissionRevision;
use App\Services\SubmissionWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function __construct(private SubmissionWorkflow $workflow)
    {
    }

    public function index(Request $request): View
    {
        $submissions = Submission::where('author_id', $request->user()->id)
            ->with(['section', 'publication'])
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        return view('account.submissions.index', compact('submissions'));
    }

    public function create(): View
    {
        return view('account.submissions.create', [
            'sections' => Section::active()->orderBy('sort_order')->get(),
            'submission' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateMetadata($request);

        $submission = DB::transaction(function () use ($request, $data) {
            $s = Submission::create([
                'reference_no' => Submission::nextReferenceNo(),
                'author_id' => $request->user()->id,
                'section_id' => $data['section_id'],
                'stage' => 'submitted',
                'current_round' => 1,
                'title' => $data['id_title'],
                'cover_letter' => $data['cover_letter'] ?? null,
                'conflict_of_interest' => $data['conflict_of_interest'] ?? null,
                'suggested_reviewers' => $data['suggested_reviewers'] ?? [],
                'metadata' => [
                    'id_title' => $data['id_title'],
                    'en_title' => $data['en_title'] ?? $data['id_title'],
                    'id_abstract' => $data['id_abstract'],
                    'en_abstract' => $data['en_abstract'] ?? $data['id_abstract'],
                    'keywords' => $data['keywords'],
                    'en_keywords' => $data['en_keywords'] ?? $data['keywords'],
                    'funding' => $data['funding'] ?? null,
                    'acknowledgments' => $data['acknowledgments'] ?? null,
                    'license' => $data['license'] ?? 'CC-BY-4.0',
                    'checklist' => [
                        'original' => (bool) $data['checklist_original'],
                        'not_published' => (bool) $data['checklist_not_published'],
                        'coauthor_approved' => (bool) $data['checklist_coauthor_approved'],
                    ],
                ],
                'submitted_at' => now(),
            ]);

            // penulis utama = user (urutan 1), co-author dari form
            $authors = $data['authors'] ?? [];
            SubmissionAuthor::create([
                'submission_id' => $s->id,
                'user_id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'orcid' => $request->user()->orcid,
                'institution' => $request->user()->institution,
                'is_corresponding' => true,
                'display_order' => 1,
            ]);
            foreach (array_values($authors) as $i => $a) {
                SubmissionAuthor::create([
                    'submission_id' => $s->id,
                    'name' => $a['name'],
                    'email' => $a['email'] ?? null,
                    'orcid' => $a['orcid'] ?? null,
                    'institution' => $a['institution'] ?? null,
                    'is_corresponding' => false,
                    'display_order' => $i + 2,
                ]);
            }

            SubmissionRevision::create([
                'submission_id' => $s->id,
                'version' => 1,
                'note' => 'Versi awal',
            ]);

            SubmissionEvent::log($s, 'submission_created', null, $request->user()->id);
            AuditLog::log($request->user()->id, 'submission.create', 'Submission', $s->id);

            return $s;
        });

        return redirect()
            ->route('account.submissions.files', $submission)
            ->with('status', 'Naskah dibuat. Lanjutkan dengan mengunggah file.');
    }

    public function show(Request $request, Submission $submission): View
    {
        $this->authorizeView($request, $submission);
        $submission->load(['section', 'authors', 'revisions', 'files', 'reviews.reviewer', 'decisions.editor', 'events.actor', 'payment', 'publication', 'messages']);

        return view('account.submissions.show', [
            'submission' => $submission,
            'isOwner' => $submission->author_id === $request->user()->id,
        ]);
    }

    public function edit(Request $request, Submission $submission): View
    {
        $this->authorizeView($request, $submission);
        abort_unless($submission->author_id === $request->user()->id, 403);
        abort_unless(in_array($submission->stage, ['submitted', 'revised'], true), 422, 'Metadata hanya dapat diedit saat tahap Submitted/Revised.');

        return view('account.submissions.create', [
            'sections' => Section::active()->orderBy('sort_order')->get(),
            'submission' => $submission,
        ]);
    }

    public function update(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorizeView($request, $submission);
        abort_unless($submission->author_id === $request->user()->id, 403);
        abort_unless(in_array($submission->stage, ['submitted', 'revised'], true), 422, 'Metadata hanya dapat diedit saat tahap Submitted/Revised.');

        $data = $this->validateMetadata($request, $submission);

        $submission->update([
            'section_id' => $data['section_id'],
            'title' => $data['id_title'],
            'cover_letter' => $data['cover_letter'] ?? null,
            'conflict_of_interest' => $data['conflict_of_interest'] ?? null,
            'suggested_reviewers' => $data['suggested_reviewers'] ?? $submission->suggested_reviewers,
            'metadata' => array_merge($submission->metadata ?? [], [
                'id_title' => $data['id_title'],
                'en_title' => $data['en_title'] ?? $data['id_title'],
                'id_abstract' => $data['id_abstract'],
                'en_abstract' => $data['en_abstract'] ?? $data['id_abstract'],
                'keywords' => $data['keywords'],
                'en_keywords' => $data['en_keywords'] ?? $data['keywords'],
                'funding' => $data['funding'] ?? null,
                'acknowledgments' => $data['acknowledgments'] ?? null,
                'license' => $data['license'] ?? 'CC-BY-4.0',
            ]),
        ]);

        SubmissionEvent::log($submission, 'metadata_updated', null, $request->user()->id);

        return redirect()->route('account.submissions.show', $submission)->with('status', 'Metadata tersimpan.');
    }

    public function submit(Request $request, Submission $submission): RedirectResponse
    {
        abort_unless($submission->author_id === $request->user()->id, 403);
        abort_unless(in_array($submission->stage, ['submitted', 'revised'], true), 422, 'Naskah tidak dapat difinalkan pada tahap ini.');

        $hasManuscript = $submission->files()->where('type', 'manuscript')->exists();
        $hasCover = $submission->files()->where('type', 'cover_letter')->exists();

        if (! $hasManuscript) {
            return back()->with('error', 'File naskah utama (manuscript) wajib diunggah.');
        }
        if (! $hasCover) {
            return back()->with('error', 'Cover letter wajib diunggah.');
        }

        $this->workflow->submit($submission, $request->user());

        return redirect()->route('account.submissions.show', $submission)
            ->with('status', 'Naskah '.$submission->reference_no.' telah dikirim. Redaksi akan meninjau naskah Anda.');
    }

    public function withdraw(Request $request, Submission $submission): RedirectResponse
    {
        abort_unless($submission->author_id === $request->user()->id, 403);

        $this->workflow->withdraw($submission, $request->user(), $request->input('reason'));

        return back()->with('status', 'Naskah ditarik.');
    }

    /* ---------------- files ---------------- */

    public function files(Request $request, Submission $submission): View
    {
        abort_unless($submission->author_id === $request->user()->id, 403);
        $submission->load('files', 'revisions');

        return view('account.submissions.files', ['submission' => $submission]);
    }

    public function storeFile(Request $request, Submission $submission): JsonResponse|RedirectResponse
    {
        abort_unless($submission->author_id === $request->user()->id, 403);
        abort_unless(in_array($submission->stage, ['submitted', 'revised'], true), 422, 'File hanya dapat diunggah saat tahap Submitted/Revised.');

        $data = $request->validate([
            'type' => ['required', 'in:manuscript,cover_letter,figure,table,supplementary'],
            'file' => ['required', 'file', 'max:51200', 'mimes:'.implode(',', SubmissionFile::WHITELIST)],
            'revision_id' => ['nullable', 'integer', 'exists:submission_revisions,id'],
        ]);

        $file = $request->file('file');
        $path = $file->store('submissions/'.$submission->id, 'local');

        SubmissionFile::create([
            'submission_id' => $submission->id,
            'revision_id' => $data['revision_id'] ?? null,
            'type' => $data['type'],
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        SubmissionEvent::log($submission, 'file_uploaded', ['type' => $data['type']], $request->user()->id);
        AuditLog::log($request->user()->id, 'submission.file_uploaded', 'Submission', $submission->id, ['type' => $data['type']]);

        $message = 'File '.$file->getClientOriginalName().' terunggah.';

        if ($request->expectsJson()) {
            $submission->load('files', 'revisions');
            return response()->json([
                'message' => $message,
                'files_card' => view('account.submissions.partials.files-list', ['submission' => $submission])->render(),
                'has_manuscript' => $submission->files->where('type', 'manuscript')->isNotEmpty(),
                'has_cover' => $submission->files->where('type', 'cover_letter')->isNotEmpty(),
            ]);
        }

        return back()->with('status', $message);
    }

    public function destroyFile(Request $request, Submission $submission, SubmissionFile $file): JsonResponse|RedirectResponse
    {
        abort_unless($submission->author_id === $request->user()->id, 403);
        abort_unless($file->submission_id === $submission->id, 404);

        Storage::disk('local')->delete($file->path);
        $name = $file->original_name;
        $file->delete();

        $message = 'File '.$name.' dihapus.';

        if ($request->expectsJson()) {
            $submission->load('files', 'revisions');
            return response()->json([
                'message' => $message,
                'files_card' => view('account.submissions.partials.files-list', ['submission' => $submission])->render(),
                'has_manuscript' => $submission->files->where('type', 'manuscript')->isNotEmpty(),
                'has_cover' => $submission->files->where('type', 'cover_letter')->isNotEmpty(),
            ]);
        }

        return back()->with('status', $message);
    }

    public function downloadFile(Request $request, Submission $submission, SubmissionFile $file)
    {
        abort_unless($file->submission_id === $submission->id, 404);
        $this->authorizeView($request, $submission);

        AuditLog::log($request->user()->id, 'submission.file_download', 'Submission', $submission->id, ['file_id' => $file->id]);

        return response()->download(Storage::disk('local')->path($file->path), $file->original_name);
    }

    /* ---------------- revision ---------------- */

    public function createRevision(Request $request, Submission $submission): View
    {
        abort_unless($submission->author_id === $request->user()->id, 403);
        abort_unless($submission->stage === 'revised', 422, 'Revisi hanya dapat dikirim saat naskah di tahap Revised.');

        $submission->load(['decisions', 'reviews']);

        return view('account.submissions.revision', ['submission' => $submission]);
    }

    public function storeRevision(Request $request, Submission $submission): RedirectResponse
    {
        abort_unless($submission->author_id === $request->user()->id, 403);

        $data = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:51200', 'mimes:'.implode(',', SubmissionFile::WHITELIST)],
        ]);

        $this->workflow->submitRevision($submission, $request->user(), $data['note'], ['manuscript' => $data['files']]);

        // setelah revisi dikirim, naskah kembali ke tahap decision (editor menilai)
        $this->workflow->transition($submission, 'decision', null, ['note' => 'revisi diterima, menunggu keputusan']);

        return redirect()->route('account.submissions.show', $submission)
            ->with('status', 'Revisi terkirim. Menunggu keputusan editor.');
    }

    /* ---------------- helpers ---------------- */

    private function authorizeView(Request $request, Submission $submission): void
    {
        $user = $request->user();
        abort_unless(
            $submission->author_id === $user->id || $user->isEditorial() || $user->isProduction(),
            403
        );
    }

    private function validateMetadata(Request $request, ?Submission $submission = null): array
    {
        return $request->validate([
            'section_id' => ['required', 'exists:sections,id'],
            'id_title' => ['required', 'string', 'max:500'],
            'en_title' => ['nullable', 'string', 'max:500'],
            'id_abstract' => ['required', 'string', 'min:900'],
            'en_abstract' => ['nullable', 'string'],
            'keywords' => ['required', 'array', 'min:3', 'max:6'],
            'keywords.*' => ['string', 'max:100'],
            'en_keywords' => ['nullable', 'array', 'min:3', 'max:6'],
            'en_keywords.*' => ['string', 'max:100'],
            'funding' => ['nullable', 'string', 'max:1000'],
            'acknowledgments' => ['nullable', 'string', 'max:5000'],
            'conflict_of_interest' => ['required', 'string', 'max:2000'],
            'cover_letter' => ['nullable', 'string', 'max:10000'],
            'license' => ['required', 'in:CC-BY-4.0'],
            'checklist_original' => ['required', 'accepted'],
            'checklist_not_published' => ['required', 'accepted'],
            'checklist_coauthor_approved' => ['required', 'accepted'],
            'suggested_reviewers' => ['nullable', 'array', 'max:3'],
            'suggested_reviewers.*.name' => ['nullable', 'string', 'max:150'],
            'suggested_reviewers.*.email' => ['nullable', 'email', 'max:190'],
            'suggested_reviewers.*.institution' => ['nullable', 'string', 'max:255'],
            'suggested_reviewers.*.reason' => ['nullable', 'string', 'max:500'],
            'authors' => ['nullable', 'array', 'max:9'],
            'authors.*.name' => ['required_with:authors', 'string', 'max:150'],
            'authors.*.email' => ['nullable', 'email', 'max:190'],
            'authors.*.orcid' => ['nullable', 'string', 'max:19'],
            'authors.*.institution' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
