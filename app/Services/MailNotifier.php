<?php

namespace App\Services;

use App\Mail\PipelineMail;
use App\Models\JournalSetting;
use App\Models\Notification;
use App\Models\Review;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * Satu pintu pengiriman notifikasi: email (queued) + in-app notification.
 * Event key mengikuti tabel event di Lampiran D.
 */
class MailNotifier
{
    /**
     * @param array $ctx field umum: ref, title, url, cta_label, lines[]
     */
    public function notify(string $key, array $ctx, ?string $locale = null): void
    {
        $setting = JournalSetting::current();
        $ctx = array_merge([
            'journal' => $setting->short_name,
            'journal_full' => $setting->name,
            'app_url' => rtrim(config('app.url'), '/'),
        ], $ctx);

        $subject = $this->subject($key, $ctx, $locale ?? 'id');

        foreach ($this->recipients($key, $ctx) as $recipient) {
            [$email, $userId] = $recipient;
            if ($email) {
                Mail::to($email)->queue(new PipelineMail($key, ['subject' => $subject] + $ctx, $locale ?? 'id'));
            }
            if ($userId) {
                Notification::send($userId, $this->inAppType($key), [
                    'submission_ref' => $ctx['ref'] ?? null,
                    'title' => $ctx['title'] ?? null,
                    'url' => $ctx['url'] ?? null,
                ]);
            }
        }
    }

    private function recipients(string $key, array $ctx): array
    {
        $editors = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('role', ['eic', 'section_editor']))
            ->get();
        $admins = User::query()->whereHas('roles', fn ($q) => $q->where('role', 'admin'))->get();

        return match ($key) {
            'submission_submitted' => $editors->map(fn (User $u) => [$u->email, $u->id])->all(),
            'reviewer_invited' => [[ $ctx['email'] ?? null, $ctx['user_id'] ?? null]],
            'reviewer_accepted', 'reviewer_declined' => [[ $ctx['editor_email'] ?? null, $ctx['editor_id'] ?? null]],
            'review_reminder', 'review_overdue' => [[ $ctx['email'] ?? null, $ctx['user_id'] ?? null]],
            'review_submitted' => $editors->map(fn (User $u) => [$u->email, $u->id])->all(),
            'decision_accept', 'decision_revise', 'decision_reject',
            'article_accepted', 'payment_verified', 'payment_rejected',
            'article_scheduled', 'article_published' => [[ $ctx['author_email'] ?? null, $ctx['author_id'] ?? null]],
            'revision_submitted' => $editors->map(fn (User $u) => [$u->email, $u->id])->all(),
            'payment_proof_received' => $admins->concat($editors)->unique('id')
                ->map(fn (User $u) => [$u->email, $u->id])->all(),
            'issue_published' => Subscriber::where('is_confirmed', true)
                ->get()->map(fn (Subscriber $s) => [$s->email, null])->all(),
            'correction_issued', 'retraction_issued' => array_merge(
                [[ $ctx['author_email'] ?? null, $ctx['author_id'] ?? null]],
                $editors->map(fn (User $u) => [$u->email, $u->id])->all()
            ),
            default => [[ $ctx['author_email'] ?? null, $ctx['author_id'] ?? null]],
        };
    }

    private function inAppType(string $key): string
    {
        return match ($key) {
            'submission_submitted' => 'new_submission',
            'submission_received' => 'submission_received',
            'review_started' => 'review_started',
            'with_editor' => 'with_editor',
            'reviewer_invited' => 'review_assigned',
            'reviewer_accepted' => 'reviewer_accepted',
            'reviewer_declined' => 'reviewer_declined',
            'review_submitted' => 'review_submitted',
            'decision_accept', 'decision_revise', 'decision_reject' => 'decision_made',
            'article_accepted' => 'article_accepted',
            'revision_submitted' => 'revision_received',
            'article_scheduled' => 'article_scheduled',
            'article_published' => 'article_published',
            'payment_proof_received' => 'payment_proof',
            'payment_verified' => 'payment_verified',
            'payment_rejected' => 'payment_rejected',
            'issue_published' => 'issue_published',
            'correction_issued', 'retraction_issued' => 'publication_status',
            default => 'notification',
        };
    }

    private function subject(string $key, array $ctx, string $locale): string
    {
        $ref = $ctx['ref'] ?? '';
        $title = $ctx['title'] ?? '';
        $j = $ctx['journal'] ?? 'JOSHNU';
        $dueAt = $ctx['due_at'] ?? '';
        $reviewer = $ctx['reviewer'] ?? '';
        $round = $ctx['round'] ?? 1;
        $invoice = $ctx['invoice'] ?? $ref;
        $issue = $ctx['issue'] ?? '';
        $doi = $ctx['doi'] ?? '';

        if ($locale === 'en') {
            return match ($key) {
                'submission_submitted' => "[{$j}] New submission: {$ref} — {$title}",
                'submission_received' => "[{$j}] We received your manuscript: {$ref}",
                'review_started' => "[{$j}] Your manuscript is under review: {$ref}",
                'with_editor' => "[{$j}] Your manuscript is with the editor: {$ref}",
                'reviewer_invited' => "Review invitation for {$ref}",
                'reviewer_accepted' => 'Reviewer accepted invitation '.$ref,
                'reviewer_declined' => 'Reviewer declined invitation '.$ref,
                'review_reminder' => "Reminder: review {$ref} due {$dueAt}",
                'review_overdue' => "Review {$ref} is overdue",
                'review_submitted' => "Review received: {$ref} ({$reviewer})",
                'decision_accept' => 'Editorial decision: Your manuscript is ACCEPTED',
                'decision_revise' => 'Editorial decision: Revision required',
                'decision_reject' => "Editorial decision regarding {$ref}",
                'revision_submitted' => "Revision received: {$ref} (round {$round})",
                'article_accepted' => 'Manuscript accepted — APC payment '.($ctx['amount'] ?? ''),
                'payment_proof_received' => "APC payment proof: {$invoice}",
                'payment_verified' => "APC payment verified: {$invoice}",
                'payment_rejected' => "Payment proof needs correction: {$invoice}",
                'article_scheduled' => "Your article is scheduled: {$issue}",
                'article_published' => "Your article has been published — DOI: {$doi}",
                'issue_published' => "New {$j} issue available: {$issue}",
                'correction_issued' => "Correction for article {$doi}",
                'retraction_issued' => "Retraction for article {$doi}",
                default => "Notification from {$j}",
            };
        }

        return match ($key) {
            'submission_submitted' => "[{$j}] Naskah baru: {$ref} — {$title}",
            'submission_received' => "[{$j}] Naskah Anda telah kami terima: {$ref}",
            'review_started' => "[{$j}] Naskah Anda sedang dalam tahap review: {$ref}",
            'with_editor' => "[{$j}] Naskah Anda sedang dinilai redaksi: {$ref}",
            'reviewer_invited' => "Undangan review naskah {$ref}",
            'reviewer_accepted' => 'Reviewer menerima undangan '.$ref,
            'reviewer_declined' => 'Reviewer menolak undangan '.$ref,
            'review_reminder' => "Pengingat: review {$ref} jatuh tempo {$dueAt}",
            'review_overdue' => "Review {$ref} melewati tenggat",
            'review_submitted' => "Review masuk: {$ref} ({$reviewer})",
            'decision_accept' => 'Keputusan editorial: Naskah Anda DITERIMA',
            'decision_revise' => 'Keputusan editorial: Revisi diperlukan',
            'decision_reject' => "Keputusan editorial: Naskah {$ref}",
            'revision_submitted' => "Revisi diterima: {$ref} (ronde {$round})",
            'article_accepted' => 'Naskah diterima — pembayaran APC '.($ctx['amount'] ?? ''),
            'payment_proof_received' => "Bukti pembayaran APC: {$invoice}",
            'payment_verified' => "Pembayaran APC terverifikasi: {$invoice}",
            'payment_rejected' => "Bukti pembayaran perlu diperbaiki: {$invoice}",
            'article_scheduled' => "Naskah Anda dijadwalkan: {$issue}",
            'article_published' => "Artikel Anda telah dipublikasikan — DOI: {$doi}",
            'issue_published' => "Issue baru {$j} terbit: {$issue}",
            'correction_issued' => "Pembetulan artikel {$doi}",
            'retraction_issued' => "Pencabutan artikel {$doi}",
            default => "Pemberitahuan dari {$j}",
        };
    }
}
