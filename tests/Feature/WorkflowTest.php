<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Section;
use App\Models\Submission;
use App\Models\SubmissionEvent;
use App\Models\User;
use App\Services\SubmissionWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowTest extends TestCase
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

    private function makeSubmission(User $author, array $overrides = []): Submission
    {
        return Submission::create(array_merge([
            'reference_no' => Submission::nextReferenceNo(),
            'author_id' => $author->id,
            'section_id' => Section::first()->id,
            'stage' => 'submitted',
            'current_round' => 1,
            'title' => 'Naskah Uji Pipeline',
            'cover_letter' => 'Hormat kami',
            'conflict_of_interest' => 'Tidak ada',
            'metadata' => [
                'id_title' => 'Naskah Uji Pipeline',
                'en_title' => 'Pipeline Test Manuscript',
                'id_abstract' => 'Abstrak uji pipeline.',
                'en_abstract' => 'Test abstract for pipeline.',
                'keywords' => ['uji', 'pipeline'],
                'license' => 'CC-BY-4.0',
            ],
            'submitted_at' => now(),
        ], $overrides));
    }

    public function test_full_pipeline_from_submission_to_publication(): void
    {
        Storage::fake('local');
        $this->seed();
        $wf = app(SubmissionWorkflow::class);

        $author = $this->user('wf-author@example.org');
        $eic = $this->user('wf-eic@example.org', ['eic', 'admin']);
        $reviewer = $this->user('wf-reviewer@example.org', ['reviewer']);

        // 1) submit
        $s = $this->makeSubmission($author);
        $wf->submit($s, $author);
        $this->assertSame('submitted', $s->fresh()->stage);
        $this->assertNotNull($s->reference_no);

        // 2) under review
        $wf->transition($s, 'under_review', $eic);
        $this->assertSame('under_review', $s->fresh()->stage);

        // 3) editor mengundang reviewer (HTTP)
        $this->actingAs($eic)
            ->post('/editor/submissions/'.$s->reference_no.'/reviewers/invite', [
                'emails' => ['wf-reviewer@example.org'],
            ])->assertRedirect();
        $review = Review::where('submission_id', $s->id)->first();
        $this->assertNotNull($review, 'review invitation should be created');
        $this->assertSame('invited', $review->status);
        $this->assertNotNull($review->invite_token);

        // 4) reviewer membuka + menerima undangan (HTTP)
        $this->get('/review/invite/'.$review->invite_token)->assertOk();
        $this->post('/review/invite/'.$review->invite_token.'/accept', [
            'email' => 'wf-reviewer@example.org',
            'institution' => 'Universitas Uji',
        ])->assertRedirect(route('review.show', $s));
        $this->assertSame('accepted', $review->fresh()->status);
        $this->assertNotNull($review->fresh()->reviewer_id);

        // 5) reviewer membuka form + submit review (HTTP)
        $this->actingAs($reviewer)->get('/review/'.$s->reference_no)->assertOk();
        $this->actingAs($reviewer)
            ->post('/review/'.$s->reference_no, [
                'recommended_decision' => 'accept',
                'rating_novelty' => 4,
                'rating_methodology' => 4,
                'rating_clarity' => 4,
                'rating_contribution' => 4,
                'comments_to_author' => 'Naskah layak terbit.',
                'comments_to_editor' => 'Disarankan accept.',
            ])->assertRedirect(route('review.index'));
        $this->assertSame('submitted', $review->fresh()->status);

        // 6) editor decide: accept (workflow service)
        $s->refresh();
        $wf->decide($s, $eic, ['type' => 'accept', 'comment_to_author' => 'Diterima.']);
        $this->assertSame('accepted', $s->fresh()->stage);
        $this->assertDatabaseHas('decisions', ['submission_id' => $s->id, 'type' => 'accept']);

        // 7) APC payment dibuat otomatis
        $this->assertDatabaseHas('payments', ['submission_id' => $s->id, 'status' => 'pending']);

        // 8) production schedule ke issue
        $issue = \App\Models\Issue::where('volume', 3)->where('number', 2)->first();
        $wf->schedule($s->fresh(), $eic, $issue, 3);
        $this->assertSame('scheduled', $s->fresh()->stage);

        // 9) publish
        $wf->publish($s->fresh(), $eic);
        $s = $s->fresh();
        $this->assertSame('published', $s->stage);

        $publication = $s->publication;
        $this->assertNotNull($publication);
        $this->assertNotNull($publication->doi);
        $this->assertNotNull($publication->published_at);

        // artikel publik bisa dibuka
        $this->get('/article/'.$publication->slug)->assertOk();

        // event tercatat
        $events = SubmissionEvent::where('submission_id', $s->id)->pluck('event');
        $this->assertTrue($events->contains('stage_changed'));
        $this->assertTrue($events->contains('article_published'));
    }

    public function test_withdraw_and_reject_paths(): void
    {
        Storage::fake('local');
        $this->seed();
        $wf = app(SubmissionWorkflow::class);

        $author = $this->user('wf2-author@example.org');
        $eic = $this->user('wf2-eic@example.org', ['eic']);

        // withdraw
        $s = $this->makeSubmission($author, ['title' => 'Naskah Tarik']);
        $wf->submit($s, $author);
        $wf->withdraw($s, $author, 'tidak lengkap');
        $this->assertSame('withdrawn', $s->fresh()->stage);

        // reject
        $s2 = $this->makeSubmission($author, ['title' => 'Naskah Tolak']);
        $wf->submit($s2, $author);
        $wf->decide($s2, $eic, ['type' => 'reject', 'comment_to_author' => 'Di luar ruang lingkup.']);
        $this->assertSame('rejected', $s2->fresh()->stage);
    }

    public function test_revision_round_flow(): void
    {
        Storage::fake('local');
        $this->seed();
        $wf = app(SubmissionWorkflow::class);

        $author = $this->user('wf4-author@example.org');
        $eic = $this->user('wf4-eic@example.org', ['eic']);

        $s = $this->makeSubmission($author, ['title' => 'Naskah Revisi']);
        $wf->submit($s, $author);
        $wf->decide($s, $eic, ['type' => 'minor_revise', 'comment_to_author' => 'Perbaiki metodologi.', 'revision_due_days' => 14]);
        $this->assertSame('revised', $s->fresh()->stage);

        // author mengunggah revisi (HTTP, file fake)
        $pdf = \Illuminate\Http\UploadedFile::fake()->create('revision.pdf', 100, 'application/pdf');
        $this->actingAs($author)
            ->post('/account/submissions/'.$s->reference_no.'/revisions', [
                'note' => 'Metodologi diperbaiki',
                'files' => [$pdf],
            ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('submission_revisions', ['submission_id' => $s->id, 'version' => 1]);
        // setelah revisi terkirim, naskah otomatis masuk tahap decision
        $this->assertSame('decision', $s->fresh()->stage);

        // editor decide accept
        $wf->decide($s->fresh(), $eic, ['type' => 'accept', 'comment_to_author' => 'Terima.']);
        $this->assertSame('accepted', $s->fresh()->stage);
    }

    public function test_invalid_transition_blocked(): void
    {
        Storage::fake('local');
        $this->seed();
        $wf = app(SubmissionWorkflow::class);

        $author = $this->user('wf3-author@example.org');
        $eic = $this->user('wf3-eic@example.org', ['eic']);

        $s = $this->makeSubmission($author, ['title' => 'Naskah Invalid']);
        $wf->submit($s, $author);

        // submitted -> published harus ditolak (state machine)
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $wf->transition($s, 'published', $eic);
    }
}
