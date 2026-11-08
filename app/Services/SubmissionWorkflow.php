<?php

namespace App\Services;

use App\Events\ArticlePublished;
use App\Events\ArticleScheduled;
use App\Events\DecisionMade;
use App\Events\RevisionSubmitted;
use App\Events\StageChanged;
use App\Events\SubmissionSubmitted;
use App\Models\AuditLog;
use App\Models\Decision;
use App\Models\Galley;
use App\Models\Issue;
use App\Models\JournalSetting;
use App\Models\Payment;
use App\Models\Publication;
use App\Models\PublicationMetadata;
use App\Models\Submission;
use App\Models\SubmissionEvent;
use App\Models\SubmissionFile;
use App\Models\SubmissionRevision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Satu-satunya service yang boleh menulis kolom `submissions.stage`.
 * Setiap transisi: validasi state machine → simpan → event log → audit →
 * pancarkan event Laravel (ekstensi) → notifikasi email/in-app.
 */
class SubmissionWorkflow
{
    public function __construct(protected MailNotifier $mailer)
    {
    }

    /**
     * Transisi stage manual (editor), tervalidasi state machine.
     */
    public function transition(Submission $s, string $toStage, ?User $actor = null, array $meta = []): Submission
    {
        abort_unless(
            $s->canTransitionTo($toStage),
            422,
            "Transisi {$s->stage} → {$toStage} tidak diizinkan oleh alur kerja."
        );

        $from = $s->stage;
        $s->update(['stage' => $toStage]);
        if ($toStage === 'withdrawn') {
            $s->update(['withdrawn_at' => now()]);
        }
        if ($toStage === 'under_review' && in_array($from, ['submitted', 'revised'], true)) {
            // masuk ronde baru
            $round = ($from === 'revised') ? $s->current_round + 1 : $s->current_round;
            $s->update(['current_round' => $round]);
        }

        SubmissionEvent::log($s, 'stage_changed', $meta, $actor?->id, $from, $toStage);
        AuditLog::log($actor?->id, 'submission.stage_changed', 'Submission', $s->id, ['from' => $from, 'to' => $toStage]);
        event(new StageChanged($s, $from, $toStage, $actor?->id, $meta));

        return $s->refresh();
    }

    public function submit(Submission $s, User $author): Submission
    {
        $s->update(['submitted_at' => $s->submitted_at ?? now()]);
        SubmissionEvent::log($s, 'submission_final', null, $author->id);
        AuditLog::log($author->id, 'submission.submit', 'Submission', $s->id);
        event(new SubmissionSubmitted($s, $author->id));

        $this->mailer->notify('submission_submitted', [
            'ref' => $s->reference_no,
            'title' => $s->title,
            'url' => route('editor.submissions.show', $s),
        ]);

        return $s;
    }

    public function withdraw(Submission $s, User $author, ?string $reason = null): Submission
    {
        abort_unless($s->canWithdraw(), 422, 'Naskah tidak dapat ditarik pada tahap ini.');
        $this->transition($s, 'withdrawn', $author, ['reason' => $reason]);

        return $s;
    }

    /**
     * Editor membuat keputusan (stage decision/under_review).
     */
    public function decide(Submission $s, User $editor, array $data): Decision
    {
        $type = $data['type'];
        if (! in_array($s->stage, ['submitted', 'under_review', 'decision', 'revised'], true)) {
            abort(422, 'Keputusan hanya dapat dibuat saat tahap review/decision.');
        }

        // pastikan di stage decision agar alur konsisten
        if ($s->stage !== 'decision') {
            $this->transition($s, 'decision', $editor, ['note' => 'memasuki tahap keputusan']);
        }

        $decision = Decision::create([
            'submission_id' => $s->id,
            'editor_id' => $editor->id,
            'round' => $s->current_round,
            'type' => $type,
            'comment_to_author' => $data['comment_to_author'],
            'internal_note' => $data['internal_note'] ?? null,
            'revision_due_at' => isset($data['revision_due_days'])
                ? now()->addDays((int) $data['revision_due_days'])
                : null,
            'decided_at' => now(),
        ]);

        $to = match ($type) {
            'accept' => 'accepted',
            'minor_revise', 'major_revise' => 'revised',
            'reject' => 'rejected',
            'continue_review' => 'under_review',
        };

        $this->transition($s, $to, $editor, ['decision_id' => $decision->id, 'decision_type' => $type]);

        event(new DecisionMade($s, $decision, $editor->id));

        $author = $s->author;
        $authorCtx = [
            'ref' => $s->reference_no,
            'title' => $s->title,
            'author_email' => $author?->email,
            'author_id' => $author?->id,
            'url' => route('account.submissions.show', $s),
            'comment' => $decision->comment_to_author,
            'revision_due_at' => optional($decision->revision_due_at)->format('d M Y'),
        ];

        if ($type === 'accept') {
            $this->mailer->notify('decision_accept', $authorCtx);
            $this->createApcIfEnabled($s, $decision);
            $this->mailer->notify('article_accepted', [
                'ref' => $s->reference_no,
                'title' => $s->title,
                'author_email' => $author?->email,
                'author_id' => $author?->id,
                'url' => route('account.submissions.payment', $s),
                'amount' => $s->payment?->formatAmount(),
            ]);
        } elseif ($type === 'reject') {
            $this->mailer->notify('decision_reject', $authorCtx);
        } else {
            $this->mailer->notify('decision_revise', $authorCtx + [
                'decision_label' => Decision::TYPE_LABELS[$type],
            ]);
        }

        return $decision;
    }

    public function submitRevision(Submission $s, User $author, string $note, array $files): SubmissionRevision
    {
        abort_unless($s->stage === 'revised', 422, 'Revisi hanya dapat dikirim saat naskah berada di tahap Revised.');

        $revision = DB::transaction(function () use ($s, $author, $note, $files) {
            $maxVersion = (int) $s->revisions()->max('version');
            $revision = SubmissionRevision::create([
                'submission_id' => $s->id,
                'version' => $maxVersion + 1,
                'note' => $note,
            ]);

            foreach ($files as $type => $items) {
                foreach ($items as $file) {
                    $path = $file->store('submissions/'.$s->id, 'local');
                    SubmissionFile::create([
                        'submission_id' => $s->id,
                        'revision_id' => $revision->id,
                        'type' => $type,
                        'original_name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                        'size_bytes' => $file->getSize(),
                        'uploaded_by' => $author->id,
                    ]);
                }
            }

            SubmissionEvent::log($s, 'revision_submitted', ['version' => $revision->version], $author->id);
            AuditLog::log($author->id, 'submission.revision', 'Submission', $s->id, ['version' => $revision->version]);
            event(new RevisionSubmitted($s, $revision, $author->id));

            return $revision;
        });

        $this->mailer->notify('revision_submitted', [
            'ref' => $s->reference_no,
            'title' => $s->title,
            'round' => $s->current_round,
            'url' => route('editor.submissions.show', $s),
        ]);

        return $revision;
    }

    /**
     * Jadwalkan naskah yang accepted ke issue → membuat Publication.
     */
    public function schedule(Submission $s, User $editor, Issue $issue, int $position = 0): Publication
    {
        abort_unless($s->stage === 'accepted', 422, 'Hanya naskah Accepted yang dapat dijadwalkan.');

        $publication = DB::transaction(function () use ($s, $issue, $position) {
            $publication = $s->publication()->first();
            if (! $publication) {
                $publication = Publication::create([
                    'submission_id' => $s->id,
                    'issue_id' => $issue->id,
                    'section_id' => $s->section_id,
                    'slug' => $this->makeSlug($s, $issue, $position),
                    'position' => $position,
                    'is_online_first' => false,
                    'doi' => $this->makeDoi($s, $issue, $position),
                ]);
                $this->syncPublicationMetadata($publication, $s);
                // salin galley VoR dari file naskah versi terakhir
                $this->copyManuscriptGalley($publication, $s);
            } else {
                $publication->update(['issue_id' => $issue->id, 'position' => $position]);
            }

            $this->transition($s, 'scheduled');
            SubmissionEvent::log($s, 'article_scheduled', ['issue' => $issue->id]);

            return $publication;
        });

        event(new ArticleScheduled($s, $publication, $editor->id));

        $this->mailer->notify('article_scheduled', [
            'ref' => $s->reference_no,
            'title' => $s->title,
            'author_email' => $s->author?->email,
            'author_id' => $s->author_id,
            'issue' => $issue->label(),
            'url' => route('account.submissions.show', $s),
        ]);

        return $publication;
    }

    /**
     * Publikasikan naskah (issue terbit atau online first) → stage published.
     */
    public function publish(Submission $s, User $actor, bool $onlineFirst = false): Publication
    {
        abort_unless($s->stage === 'scheduled', 422, 'Hanya naskah Scheduled yang dapat dipublikasikan.');

        $publication = $s->publication()->firstOrFail();

        $publication->update([
            'published_at' => now(),
            'is_online_first' => $onlineFirst && $publication->issue_id === null,
        ]);

        // tandai galley VoR
        $vor = $publication->galleys()->where('type', 'article_pdf')->first();
        if ($vor) {
            $publication->galleys()->update(['is_version_of_record' => false]);
            $vor->update(['is_version_of_record' => true]);
        }

        $this->transition($s, 'published', $actor);
        SubmissionEvent::log($s, 'article_published', ['doi' => $publication->doi]);
        AuditLog::log($actor->id, 'publication.publish', 'Publication', $publication->id);
        event(new ArticlePublished($s, $publication, $actor->id));

        // deposit DOI via queue bila Crossref dikonfigurasi
        \App\Jobs\DepositDoi::dispatch($publication);

        $this->mailer->notify('article_published', [
            'ref' => $s->reference_no,
            'title' => $s->title,
            'author_email' => $s->author?->email,
            'author_id' => $s->author_id,
            'issue' => $publication->issue?->label() ?? 'Online First',
            'doi' => $publication->doi ?? '-',
            'url' => route('articles.show', $publication->slug),
        ]);

        return $publication->refresh();
    }

    /* ---------------- helpers ---------------- */

    private function createApcIfEnabled(Submission $s, Decision $decision): void
    {
        $setting = JournalSetting::current();
        if (! $setting->apc_enabled) {
            return;
        }

        Payment::firstOrCreate(
            ['submission_id' => $s->id],
            [
                'invoice_no' => Submission::nextInvoiceNo(),
                'amount' => $setting->apc_amount,
                'currency' => $setting->apc_currency,
                'status' => 'pending',
                'due_at' => now()->addDays(14),
            ]
        );
    }

    private function makeSlug(Submission $s, Issue $issue, int $position): string
    {
        $base = 'joshnu-v'.$issue->volume.'i'.$issue->number.'-'.str_pad((string) ($position + 1), 3, '0', STR_PAD_LEFT);

        return Publication::where('slug', 'like', $base.'%')->exists()
            ? $base.'-'.Str::lower(Str::slug(Str::limit($s->title, 30, '')))
            : $base;
    }

    private function makeDoi(Submission $s, Issue $issue, int $position): ?string
    {
        $setting = JournalSetting::current();
        if (! $setting->doi_prefix) {
            return null;
        }

        $seq = str_pad((string) ($position + 1), 3, '0', STR_PAD_LEFT);

        return $setting->doi_prefix.'/joshnu.v'.$issue->volume.'i'.$issue->number.'.'.$seq;
    }

    public function syncPublicationMetadata(Publication $p, Submission $s): void
    {
        $meta = $s->metadata ?? [];
        foreach (['id', 'en'] as $lang) {
            $title = $meta[$lang.'_title'] ?? null;
            $abstract = $meta[$lang.'_abstract'] ?? null;
            if (! $title || ! $abstract) {
                continue;
            }
            PublicationMetadata::updateOrCreate(
                ['publication_id' => $p->id, 'lang' => $lang],
                [
                    'title' => $title,
                    'abstract' => $abstract,
                    'keywords' => $lang === 'en'
                        ? ($meta['en_keywords'] ?? $meta['keywords'] ?? [])
                        : ($meta['keywords'] ?? []),
                ]
            );
        }
    }

    public function copyManuscriptGalley(Publication $p, Submission $s): void
    {
        if ($p->galleys()->where('type', 'article_pdf')->exists()) {
            return;
        }

        $file = $s->files()->where('type', 'manuscript')->latest('id')->first();
        if (! $file) {
            return;
        }

        $target = 'publications/'.$p->id.'/article.pdf';
        if (! \Storage::disk('local')->exists($target)) {
            \Storage::disk('local')->copy($file->path, $target);
        }

        Galley::create([
            'publication_id' => $p->id,
            'type' => 'article_pdf',
            'label' => 'PDF (Version of Record)',
            'path' => $target,
            'mime_type' => $file->mime_type,
            'size_bytes' => $file->size_bytes,
            'is_version_of_record' => true,
        ]);
    }
}
