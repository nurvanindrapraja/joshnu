<?php

namespace Tests\Feature;

use App\Mail\PipelineMail;
use App\Models\Section;
use App\Models\Submission;
use App\Models\User;
use App\Services\SubmissionWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Setiap fase penerbitan → email ke penulis;
 * undangan reviewer (internal/eksternal) → email.
 */
class AuthorPipelineEmailTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, array $roles = ['author']): User
    {
        $u = User::firstOrCreate(['email' => $email], [
            'name' => ucfirst(explode('@', $email)[0]),
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);
        foreach ($roles as $r) {
            $u->roles()->firstOrCreate(['role' => $r]);
        }

        return $u;
    }

    private function makeSubmission(User $author): Submission
    {
        return Submission::create([
            'reference_no' => Submission::nextReferenceNo(),
            'author_id' => $author->id,
            'section_id' => Section::first()->id,
            'stage' => 'submitted',
            'current_round' => 1,
            'title' => 'Naskah Uji Email Pipeline',
            'metadata' => ['id_title' => 'Naskah Uji Email Pipeline'],
            'submitted_at' => now(),
        ]);
    }

    private function queuedKey(string $key, callable $filter): void
    {
        Mail::assertQueued(PipelineMail::class, function (PipelineMail $mail) use ($key, $filter) {
            return $mail->key === $key && $filter($mail->ctx);
        });
    }

    public function test_author_gets_confirmation_email_when_manuscript_submitted(): void
    {
        Storage::fake('local');
        Mail::fake();
        $this->seed();
        $wf = app(SubmissionWorkflow::class);

        $author = $this->user('pe-author@example.org');
        $s = $this->makeSubmission($author);

        $wf->submit($s, $author);

        // acknowledgment ke penulis
        $this->queuedKey('submission_received', fn (array $ctx) => ($ctx['author_email'] ?? null) === $author->email);
        // editor juga tetap mendapat notifikasi naskah baru
        $this->queuedKey('submission_submitted', fn (array $ctx) => true);
    }

    public function test_author_gets_email_when_review_starts(): void
    {
        Storage::fake('local');
        Mail::fake();
        $this->seed();
        $wf = app(SubmissionWorkflow::class);

        $author = $this->user('pe-review@example.org');
        $eic = $this->user('pe-eic@example.org', ['eic', 'admin']);
        $s = $this->makeSubmission($author);

        $wf->transition($s->fresh(), 'under_review', $eic);

        $this->queuedKey('review_started', fn (array $ctx) => ($ctx['author_email'] ?? null) === $author->email);
    }

    public function test_author_gets_email_when_with_editor(): void
    {
        Storage::fake('local');
        Mail::fake();
        $this->seed();
        $wf = app(SubmissionWorkflow::class);

        $author = $this->user('pe-editor@example.org');
        $eic = $this->user('pe-eic2@example.org', ['eic', 'admin']);
        $s = $this->makeSubmission($author);
        $wf->transition($s, 'under_review', $eic);

        $wf->transition($s->fresh(), 'decision', $eic);

        $this->queuedKey('with_editor', fn (array $ctx) => ($ctx['author_email'] ?? null) === $author->email);
    }

    public function test_decision_still_notifies_author(): void
    {
        Storage::fake('local');
        Mail::fake();
        $this->seed();
        $wf = app(SubmissionWorkflow::class);

        $author = $this->user('pe-decide@example.org');
        $eic = $this->user('pe-eic3@example.org', ['eic', 'admin']);
        $s = $this->makeSubmission($author);
        $wf->transition($s, 'under_review', $eic);

        $wf->decide($s->fresh(), $eic, [
            'type' => 'accept',
            'comment_to_author' => 'Luar biasa.',
        ]);

        // fase keputusan → email decision_accept + article_accepted (APC)
        $this->queuedKey('decision_accept', fn (array $ctx) => ($ctx['author_email'] ?? null) === $author->email);
    }

    public function test_external_reviewer_invitation_sent_by_email(): void
    {
        Storage::fake('local');
        Mail::fake();
        $this->seed();

        $eic = $this->user('pe-invite-eic@example.org', ['eic', 'admin']);
        $s = $this->makeSubmission($this->user('pe-invite-author@example.org'));

        $this->actingAs($eic)
            ->post('/editor/submissions/'.$s->reference_no.'/reviewers/invite', [
                'emails' => ['external-reviewer@univ-satelit.ac.id'],
            ])
            ->assertRedirect();

        // permohonan reviewer EKSTERNAL terkirim via email
        $this->queuedKey('reviewer_invited', fn (array $ctx) => ($ctx['email'] ?? null) === 'external-reviewer@univ-satelit.ac.id');
        // dan penulis tahu naskahnya masuk review
        $this->queuedKey('review_started', fn (array $ctx) => ($ctx['author_email'] ?? null) === 'pe-invite-author@example.org');
    }
}
