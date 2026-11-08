<?php

namespace App\Listeners\Submission;

use App\Events\StageChanged;
use App\Events\SubmissionSubmitted;
use App\Services\MailNotifier;

/**
 * Notifikasi pipeline untuk PENULIS (email + in-app) — auto-discovered.
 *
 *  - naskah dikirim (submission_submitted)  → email terima kasih/acknowledgment
 *  - masuk under_review                     → "naskah sedang dalam peer review"
 *  - masuk decision                         → "naskah sedang dinilai redaksi"
 *
 * Fase lain sudah punya email khusus (lihat MailNotifier):
 *  keputusan (accept/revise/reject), APC, pembayaran, jadwal issue, publikasi.
 * Undangan reviewer (internal & eksternal) dikirim saat reviewer di-assign.
 */
class NotifyAuthorOnPipeline
{
    public function __construct(private MailNotifier $mailer)
    {
    }

    public function handleSubmissionSubmitted(SubmissionSubmitted $e): void
    {
        $s = $e->submission;

        $this->mailer->notify('submission_received', [
            'ref' => $s->reference_no,
            'title' => $s->title,
            'author_email' => $s->author?->email,
            'author_id' => $s->author_id,
            'url' => route('account.submissions.show', $s),
            'cta_label' => 'Lihat Naskah Saya',
        ]);
    }

    public function handleStageChanged(StageChanged $e): void
    {
        $s = $e->submission;
        if ($s->author_id === null) {
            return;
        }

        $ctx = [
            'ref' => $s->reference_no,
            'title' => $s->title,
            'author_email' => $s->author?->email,
            'author_id' => $s->author_id,
            'url' => route('account.submissions.show', $s),
        ];

        match ($e->toStage) {
            'under_review' => $this->mailer->notify('review_started', $ctx + [
                'lines' => [
                    'Tahap saat ini' => 'Peer Review',
                    'Catatan' => 'Reviewer telah ditugaskan untuk menilai naskah Anda.',
                ],
            ]),
            'decision' => $this->mailer->notify('with_editor', $ctx + [
                'lines' => [
                    'Tahap saat ini' => 'Penilaian Redaksi',
                    'Catatan' => 'Naskah Anda sedang dipertimbangkan oleh editor untuk keputusan.',
                ],
            ]),
            default => null,
        };
    }
}
