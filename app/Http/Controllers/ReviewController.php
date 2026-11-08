<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Submission;
use App\Http\Concerns\RespondsToAjax;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Portal reviewer: queue + detail + form review.
 * Reviewer internal (role reviewer) melihat semua yang di-assign;
 * reviewer via token hanya bisa lewat halaman undangan.
 */
class ReviewController extends Controller
{
    use RespondsToAjax;
    public function index(Request $request): View
    {
        $user = $request->user();

        $reviews = Review::where('reviewer_id', $user->id)
            ->orWhere('invite_email', $user->email)
            ->with('submission.section')
            ->latest('due_at')
            ->get()
            ->filter(fn (Review $r) => in_array($r->status, ['invited', 'accepted', 'in_progress', 'overdue', 'submitted']));

        return view('review.index', ['reviews' => $reviews]);
    }

    public function show(Request $request, Submission $submission): View
    {
        $user = $request->user();
        $review = $submission->reviews()
            ->where(fn ($q) => $q->where('reviewer_id', $user->id)->orWhere('invite_email', $user->email))
            ->first();

        abort_unless($review && in_array($review->status, ['invited', 'accepted', 'in_progress', 'overdue', 'submitted']), 403);

        $submission->load(['files', 'revisions', 'section']);
        $review->load('reviewer');

        // mulai in_progress saat pertama kali dibuka
        if ($review->status === 'accepted') {
            $review->update(['status' => 'in_progress']);
        }

        return view('review.show', compact('submission', 'review'));
    }

    public function update(Request $request, Submission $submission): \Symfony\Component\HttpFoundation\Response
    {
        $user = $request->user();
        $review = $submission->reviews()
            ->where(fn ($q) => $q->where('reviewer_id', $user->id)->orWhere('invite_email', $user->email))
            ->firstOrFail();

        abort_unless(in_array($review->status, ['invited', 'accepted', 'in_progress', 'overdue']), 422, 'Review sudah dikirim.');

        $data = $request->validate([
            'recommended_decision' => ['required', 'in:accept,minor_revise,major_revise,reject'],
            'rating_novelty' => ['nullable', 'integer', 'between:1,5'],
            'rating_methodology' => ['nullable', 'integer', 'between:1,5'],
            'rating_clarity' => ['nullable', 'integer', 'between:1,5'],
            'rating_contribution' => ['nullable', 'integer', 'between:1,5'],
            'comments_to_author' => ['required', 'string', 'max:20000'],
            'comments_to_editor' => ['nullable', 'string', 'max:20000'],
            'file' => ['nullable', 'file', 'max:51200', 'mimes:pdf,doc,docx'],
        ]);

        $review->update($data + [
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('submissions/'.$submission->id.'/reviews', 'local');
            \App\Models\SubmissionFile::create([
                'submission_id' => $submission->id,
                'type' => 'review_form',
                'original_name' => $request->file('file')->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $request->file('file')->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => $request->file('file')->getSize(),
                'uploaded_by' => $user->id,
            ]);
        }

        \App\Models\SubmissionEvent::log($submission, 'review_submitted', [
            'review_id' => $review->id, 'recommendation' => $data['recommended_decision'],
        ], $user->id);
        \App\Models\AuditLog::log($user->id, 'review.submit', 'Review', $review->id);

        event(new \App\Events\ReviewSubmitted($submission, $review, $user->id));

        app(\App\Services\MailNotifier::class)->notify('review_submitted', [
            'ref' => $submission->reference_no,
            'title' => $submission->title,
            'reviewer' => $user->name,
            'recommendation' => $data['recommended_decision'],
            'url' => route('editor.submissions.show', $submission),
        ]);

        return $this->ajaxOrRedirect($request, 'Review terkirim. Terima kasih!', redirect()->route('review.index'));
    }

    public function storeFile(Request $request, Submission $submission): \Symfony\Component\HttpFoundation\Response
    {
        $user = $request->user();
        $review = $submission->reviews()
            ->where(fn ($q) => $q->where('reviewer_id', $user->id)->orWhere('invite_email', $user->email))
            ->firstOrFail();

        $data = $request->validate([
            'file' => ['required', 'file', 'max:51200', 'mimes:pdf,doc,docx'],
        ]);

        $file = $request->file('file');
        $path = $file->store('submissions/'.$submission->id.'/reviews', 'local');

        \App\Models\SubmissionFile::create([
            'submission_id' => $submission->id,
            'type' => 'review_form',
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $user->id,
        ]);

        return $this->ajaxOrRedirect($request, 'File review terunggah.', back());
    }

    public function downloadFile(Request $request, Submission $submission, \App\Models\SubmissionFile $file): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $user = $request->user();

        $isReviewer = $submission->reviews()
            ->where(fn ($q) => $q->where('reviewer_id', $user->id)->orWhere('invite_email', $user->email))
            ->whereIn('status', ['invited', 'accepted', 'in_progress', 'overdue', 'submitted'])
            ->exists();

        abort_unless($isReviewer || $user->isEditorial(), 403);
        abort_unless($file->submission_id === $submission->id, 404);

        \App\Models\AuditLog::log($user->id, 'submission.file_download', 'Submission', $submission->id, ['file_id' => $file->id]);

        return response()->download(\Storage::disk('local')->path($file->path), $file->original_name);
    }
}
