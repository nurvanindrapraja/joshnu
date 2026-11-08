<?php

namespace App\Http\Controllers;

use App\Events\ReviewerInvited;
use App\Events\ReviewerResponded;
use App\Models\AuditLog;
use App\Models\JournalSetting;
use App\Models\Review;
use App\Models\Submission;
use App\Models\User;
use App\Services\MailNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Undangan reviewer berbasis token (bisa tanpa akun penuh).
 */
class ReviewerInviteController extends Controller
{
    public function __construct(private MailNotifier $mailer)
    {
    }

    public function show(string $token): View
    {
        $review = Review::where('invite_token', $token)->with('submission.section')->firstOrFail();
        $submission = $review->submission;

        return view('review.invite', compact('review', 'submission'));
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $review = Review::where('invite_token', $token)->with('submission')->firstOrFail();

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190'],
            'institution' => ['nullable', 'string', 'max:255'],
            'expertise' => ['nullable', 'string', 'max:500'],
        ]);

        // auto-buat profil ringkas bila belum punya akun
        $user = User::firstOrCreate(
            ['email' => strtolower($data['email'])],
            [
                'name' => $data['name'] ?? explode('@', $data['email'])[0],
                'password' => bcrypt(Str::random(32)),
                'institution' => $data['institution'] ?? null,
                'expertise' => $data['expertise'] ?? null,
                'email_verified_at' => now(),
            ]
        );
        if (! $user->hasRole('reviewer')) {
            $user->roles()->firstOrCreate(['role' => 'reviewer']);
        }

        $review->update([
            'reviewer_id' => $user->id,
            'invite_email' => strtolower($data['email']),
            'status' => 'accepted',
        ]);

        \App\Models\Assignment::firstOrCreate([
            'submission_id' => $review->submission_id,
            'user_id' => $user->id,
            'type' => 'reviewer',
        ], [
            'assigned_by' => $review->submission->author_id,
            'assigned_at' => now(),
        ]);

        event(new ReviewerResponded($review->submission, $review, true, $user->id));
        AuditLog::log($user->id, 'review.invite_accepted', 'Review', $review->id);

        $this->mailer->notify('reviewer_accepted', [
            'ref' => $review->submission->reference_no,
            'reviewer' => $user->name,
            'editor_id' => $this->inviterId($review),
            'url' => route('editor.submissions.show', $review->submission),
        ]);

        // login sebagai reviewer baru
        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->route('review.show', $review->submission)
            ->with('status', 'Undangan diterima. Anda dapat mulai mereview naskah.');
    }

    public function decline(Request $request, string $token): RedirectResponse
    {
        $review = Review::where('invite_token', $token)->with('submission')->firstOrFail();

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $review->update([
            'status' => 'declined',
            'decline_reason' => $data['reason'] ?? null,
        ]);

        event(new ReviewerResponded($review->submission, $review, false));
        AuditLog::log(null, 'review.invite_declined', 'Review', $review->id, ['email' => $review->invite_email]);

        $this->mailer->notify('reviewer_declined', [
            'ref' => $review->submission->reference_no,
            'reviewer' => $review->invite_email,
            'reason' => $data['reason'] ?? '-',
            'editor_id' => $this->inviterId($review),
            'url' => route('editor.submissions.show', $review->submission),
        ]);

        // Dari halaman review (AJAX): balas JSON; dari halaman undangan: tampil halaman.
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => 'Undangan ditolak. Terima kasih atas respons Anda.']);
        }

        return view('review.invite-declined', ['review' => $review]);
    }

    private function inviterId(Review $review): ?int
    {
        return $review->submission->assignments()
            ->where('type', 'editor')
            ->latest('assigned_at')
            ->value('assigned_by')
            ?? $review->submission->author_id;
    }
}
