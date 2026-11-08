<?php

namespace App\Http\Controllers;

use App\Events\PublicationStatusChanged;
use App\Jobs\DepositDoi;
use App\Jobs\GenerateArticlePdf;
use App\Models\AuditLog;
use App\Models\Issue;
use App\Models\JournalSetting;
use App\Models\Publication;
use App\Models\Submission;
use App\Http\Concerns\RespondsToAjax;
use App\Services\SubmissionWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProductionController extends Controller
{
    use RespondsToAjax;

    public function __construct(private SubmissionWorkflow $workflow)
    {
    }

    public function index(Request $request): View
    {
        $publications = Publication::query()
            ->whereHas('submission', fn ($q) => $q->whereIn('stage', ['accepted', 'scheduled', 'published']))
            ->with(['submission', 'issue', 'galleys'])
            ->latest('published_at')
            ->paginate(20)
            ->withQueryString();

        return view('production.index', compact('publications'));
    }

    public function schedule(Request $request, Submission $submission): Response
    {
        abort_unless($request->user()->isEditorial() || $request->user()->isProduction(), 403);

        $data = $request->validate([
            'issue_id' => ['required', 'exists:issues,id'],
            'position' => ['nullable', 'integer', 'min:0', 'max:100'],
            'online_first' => ['sometimes', 'boolean'],
        ]);

        $issue = Issue::findOrFail($data['issue_id']);

        if (! empty($data['online_first'])) {
            // online first: publikasi tanpa menunggu issue
            $publication = $this->workflow->schedule($submission, $request->user(), $issue, (int) $data['position']);
            $this->workflow->publish($submission, $request->user(), true);
            return $this->ajaxOrRedirect($request, 'Artikel dipublikasikan (online first).', back());
        }

        $this->workflow->schedule($submission, $request->user(), $issue, (int) $data['position']);

        return $this->ajaxOrRedirect($request, 'Artikel dijadwalkan ke '.$issue->label().'.', back());
    }

    public function updateMetadata(Request $request, Publication $publication): RedirectResponse
    {
        $this->authorizeProduction($request);

        $data = $request->validate([
            'id_title' => ['required', 'string', 'max:500'],
            'id_abstract' => ['required', 'string'],
            'id_keywords' => ['required', 'string', 'max:1000'],
            'en_title' => ['nullable', 'string', 'max:500'],
            'en_abstract' => ['nullable', 'string'],
            'en_keywords' => ['nullable', 'string', 'max:1000'],
        ]);

        $s = $publication->submission;
        $meta = $s->metadata ?? [];
        $meta['id_title'] = $data['id_title'];
        $meta['id_abstract'] = $data['id_abstract'];
        $meta['keywords'] = array_values(array_filter(array_map('trim', explode(',', $data['id_keywords']))));
        if (! empty($data['en_title'])) {
            $meta['en_title'] = $data['en_title'];
            $meta['en_abstract'] = $data['en_abstract'] ?? $data['id_abstract'];
            $meta['en_keywords'] = $data['en_keywords'] ? array_values(array_filter(array_map('trim', explode(',', $data['en_keywords'])))) : $meta['keywords'];
        }
        $s->update(['title' => $data['id_title'], 'metadata' => $meta]);

        $this->workflow->syncPublicationMetadata($publication->refresh(), $s);
        AuditLog::log($request->user()->id, 'publication.metadata_update', 'Publication', $publication->id);

        return back()->with('status', 'Metadata final tersimpan.');
    }

    public function uploadProof(Request $request, Submission $submission): RedirectResponse
    {
        $this->authorizeProduction($request);

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:51200'],
        ]);

        $file = $request->file('file');
        $path = $file->store('submissions/'.$submission->id.'/proof', 'local');

        \App\Models\SubmissionFile::create([
            'submission_id' => $submission->id,
            'type' => 'proof',
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        // jika publication sudah ada, buat galley proof
        $publication = $submission->publication()->first();
        if ($publication) {
            app(\App\Services\PdfService::class);
            $target = 'publications/'.$publication->id.'/proof.pdf';
            Storage::disk('local')->copy($path, $target);
            $galley = $publication->galleys()->where('type', 'proof')->first();
            if ($galley) {
                $galley->update(['path' => $target, 'size_bytes' => $file->getSize()]);
            } else {
                $publication->galleys()->create([
                    'type' => 'proof', 'label' => 'PDF Proof', 'path' => $target,
                    'mime_type' => 'application/pdf', 'size_bytes' => $file->getSize(),
                ]);
            }
        }

        AuditLog::log($request->user()->id, 'production.proof', 'Submission', $submission->id);

        return back()->with('status', 'Galley proof terunggah.');
    }

    public function generatePdf(Request $request, Publication $publication): RedirectResponse
    {
        $this->authorizeProduction($request);

        GenerateArticlePdf::dispatch($publication, false);

        return back()->with('status', 'Job PDF of record dijalankan (queue).');
    }

    public function pdfStatus(Publication $publication): \Illuminate\Http\JsonResponse
    {
        $this->authorizeProduction(request());

        $galley = $publication->galleys()->where('type', 'article_pdf')->first();

        return response()->json(['data' => [
            'exists' => (bool) $galley && Storage::disk('local')->exists($galley->path),
            'galley_id' => $galley?->id,
            'size' => $galley?->size_bytes,
        ]]);
    }

    public function generateJats(Request $request, Publication $publication): RedirectResponse
    {
        $this->authorizeProduction($request);

        app(\App\Services\PdfService::class)->generateJatsXml($publication);

        return back()->with('status', 'JATS XML dibuat.');
    }

    public function depositDoi(Request $request, Publication $publication): RedirectResponse
    {
        $this->authorizeProduction($request);

        // validasi metadata dasar sebelum deposit
        abort_if($publication->metadata->count() === 0, 422, 'Metadata artikel belum lengkap.');
        abort_if(! $publication->doi, 422, 'DOI belum tersedia untuk artikel ini.');

        DepositDoi::dispatch($publication);

        return back()->with('status', 'Deposit DOI ke Crossref dijalankan (queue).');
    }

    public function doiStatus(Publication $publication): \Illuminate\Http\JsonResponse
    {
        $this->authorizeProduction(request());

        return response()->json(['data' => [
            'doi' => $publication->doi,
            'doi_status' => $publication->doi_status,
            'doi_response' => $publication->doi_response,
        ]]);
    }

    public function publish(Request $request, Publication $publication): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin', 'eic', 'production'), 403);

        $submission = $publication->submission;
        abort_unless(in_array($submission->stage, ['accepted', 'scheduled'], true), 422, 'Naskah belum siap dipublikasikan.');

        if ($submission->stage === 'accepted') {
            // harus dijadwalkan dulu (issue atau online first)
            abort(422, 'Jadwalkan artikel ke issue (atau online first) terlebih dahulu.');
        }

        $this->workflow->publish($submission, $request->user(), $publication->issue_id === null);

        return back()->with('status', 'Artikel dipublikasikan! DOI: '.$publication->doi);
    }

    public function unpublish(Request $request, Publication $publication): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin', 'eic'), 403);

        $publication->delete(); // soft delete = withdraw publik
        AuditLog::log($request->user()->id, 'publication.unpublish', 'Publication', $publication->id);

        return back()->with('status', 'Artikel di-unpublish (dihapus dari publik).');
    }

    public function correct(Request $request, Publication $publication): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin', 'eic'), 403);

        $data = $request->validate(['note' => ['required', 'string', 'max:5000']]);

        $publication->update(['article_status' => 'corrected', 'correction_note' => $data['note']]);
        AuditLog::log($request->user()->id, 'publication.correction', 'Publication', $publication->id);
        event(new PublicationStatusChanged($publication->submission, $publication, 'corrected', $request->user()->id));

        $this->notifyAuthors($publication, 'correction_issued', $request->user());

        return back()->with('status', 'Correction diterbitkan.');
    }

    public function retract(Request $request, Publication $publication): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin', 'eic'), 403);

        $data = $request->validate(['reason' => ['required', 'string', 'max:5000']]);

        $publication->update(['article_status' => 'retracted', 'retraction_reason' => $data['reason']]);
        AuditLog::log($request->user()->id, 'publication.retraction', 'Publication', $publication->id);
        event(new PublicationStatusChanged($publication->submission, $publication, 'retracted', $request->user()->id));

        $this->notifyAuthors($publication, 'retraction_issued', $request->user());

        return back()->with('status', 'Retraction diterbitkan (label permanen).');
    }

    private function notifyAuthors(Publication $p, string $key, \App\Models\User $user): void
    {
        app(\App\Services\MailNotifier::class)->notify($key, [
            'ref' => $p->submission->reference_no,
            'title' => $p->titleFor('id'),
            'doi' => $p->doi ?? $p->slug,
            'author_email' => $p->submission->author?->email,
            'author_id' => $p->submission->author_id,
            'url' => route('articles.show', $p->slug),
        ]);
    }

    private function authorizeProduction(Request $request): void
    {
        abort_unless($request->user()->isProduction(), 403);
    }
}
