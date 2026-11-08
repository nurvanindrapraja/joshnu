<?php

namespace App\Http\Controllers;

use App\Events\ReviewerInvited;
use App\Http\Concerns\RespondsToAjax;
use App\Models\AuditLog;
use App\Models\Assignment;
use App\Models\JournalSetting;
use App\Models\Review;
use App\Models\Submission;
use App\Models\User;
use App\Services\MailNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReviewerManagementController extends Controller
{
    use RespondsToAjax;

    public function __construct(private MailNotifier $mailer)
    {
    }

    /** Cari reviewer internal (db reviewer). */
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $reviewers = User::query()
            ->whereHas('roles', fn ($r) => $r->where('role', 'reviewer'))
            ->when($q, function ($query) use ($q) {
                $query->where(fn ($w) => $w
                    ->where('name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('institution', 'like', '%'.$q.'%')
                    ->orWhere('expertise', 'like', '%'.$q.'%'));
            })
            ->withCount(['reviews as completed_reviews_count' => fn ($r) => $r->where('status', 'submitted')])
            ->paginate(20)
            ->withQueryString();

        return view('editor.reviewers.index', compact('reviewers', 'q'));
    }

    /** Assign reviewer internal ke naskah. */
    public function assign(Request $request, Submission $submission): Response
    {
        $this->authorize($request);

        $data = $request->validate([
            'reviewer_ids' => ['required', 'array', 'min:1', 'max:5'],
            'reviewer_ids.*' => ['exists:users,id'],
            'due_days' => ['nullable', 'integer', 'min:1', 'max:180'],
        ]);

        $due = now()->addDays((int) ($data['due_days'] ?? JournalSetting::current()->review_deadline_days));

        $created = DB::transaction(function () use ($submission, $data, $request, $due) {
            $created = [];
            foreach ($data['reviewer_ids'] as $uid) {
                $user = User::findOrFail($uid);
                if (! $user->hasRole('reviewer')) {
                    $user->roles()->firstOrCreate(['role' => 'reviewer']);
                }

                $review = Review::firstOrCreate(
                    [
                        'submission_id' => $submission->id,
                        'reviewer_id' => $user->id,
                        'round' => $submission->current_round,
                    ],
                    [
                        'status' => 'invited',
                        'due_at' => $due,
                        'invite_email' => $user->email,
                        'invite_token' => Str::random(64),
                    ]
                );

                Assignment::firstOrCreate(
                    ['submission_id' => $submission->id, 'user_id' => $user->id, 'type' => 'reviewer'],
                    ['assigned_by' => $request->user()->id, 'assigned_at' => now()]
                );

                // pastikan stage under_review
                if (in_array($submission->stage, ['submitted', 'revised', 'decision'], true)) {
                    $submission->refresh();
                    if ($submission->canTransitionTo('under_review')) {
                        app(\App\Services\SubmissionWorkflow::class)->transition($submission, 'under_review', $request->user(), ['note' => 'reviewer di-assign']);
                    }
                }

                event(new ReviewerInvited($submission->refresh(), $review, $request->user()->id));
                $created[] = $review;
            }

            return $created;
        });

        foreach ($created as $review) {
            $this->mailer->notify('reviewer_invited', [
                'ref' => $submission->reference_no,
                'title' => $review->blindTitle(),
                'email' => $review->invite_email,
                'user_id' => $review->reviewer_id,
                'section' => $submission->section?->name,
                'due_at' => $review->due_at->format('d M Y'),
                'url' => route('reviewer.invite', $review->invite_token),
            ]);
        }

        AuditLog::log($request->user()->id, 'reviewer.assign', 'Submission', $submission->id, [
            'reviewers' => $data['reviewer_ids'],
        ]);

        return $this->ajaxOrRedirect(
            $request,
            count($created).' reviewer di-assign.',
            redirect()->route('editor.submissions.show', $submission)
        );
    }

    /** Invite reviewer eksternal via email + token. */
    public function invite(Request $request, Submission $submission): Response
    {
        $this->authorize($request);

        $data = $request->validate([
            'emails' => ['required', 'array', 'min:1', 'max:5'],
            'emails.*' => ['email', 'max:190'],
            'due_days' => ['nullable', 'integer', 'min:1', 'max:180'],
        ]);

        $due = now()->addDays((int) ($data['due_days'] ?? JournalSetting::current()->review_deadline_days));
        $created = [];

        foreach ($data['emails'] as $email) {
            $email = strtolower(trim($email));
            $review = Review::firstOrCreate(
                [
                    'submission_id' => $submission->id,
                    'invite_email' => $email,
                    'round' => $submission->current_round,
                ],
                [
                    'status' => 'invited',
                    'due_at' => $due,
                    'invite_token' => Str::random(64),
                ]
            );
            $created[] = $review;
        }

        if (in_array($submission->stage, ['submitted', 'revised', 'decision'], true) && $submission->canTransitionTo('under_review')) {
            app(\App\Services\SubmissionWorkflow::class)->transition($submission, 'under_review', $request->user(), ['note' => 'reviewer diundang']);
        }

        foreach ($created as $review) {
            event(new ReviewerInvited($submission->refresh(), $review, $request->user()->id));
            $this->mailer->notify('reviewer_invited', [
                'ref' => $submission->reference_no,
                'title' => $review->blindTitle(),
                'email' => $email,
                'user_id' => null,
                'section' => $submission->section?->name,
                'due_at' => $review->due_at->format('d M Y'),
                'url' => route('reviewer.invite', $review->invite_token),
            ]);
        }

        AuditLog::log($request->user()->id, 'reviewer.invite', 'Submission', $submission->id, ['emails' => $data['emails']]);

        return $this->ajaxOrRedirect($request, count($created).' undangan review terkirim.', back());
    }

    /** Batalkan invite/assign reviewer. */
    public function cancel(Request $request, Submission $submission, Review $review): Response
    {
        $this->authorize($request);
        abort_unless($review->submission_id === $submission->id, 404);

        $review->update(['status' => 'cancelled']);
        Assignment::where('submission_id', $submission->id)
            ->where('user_id', $review->reviewer_id)
            ->where('type', 'reviewer')
            ->delete();

        AuditLog::log($request->user()->id, 'reviewer.cancel', 'Review', $review->id);

        return $this->ajaxOrRedirect($request, 'Reviewer dibatalkan.', back());
    }

    private function authorize(Request $request): void
    {
        abort_unless($request->user()->isEditorial(), 403);
    }
}
