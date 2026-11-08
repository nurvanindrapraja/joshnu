<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Review;
use App\Models\Section;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Kontrak AJAX halaman review (portal reviewer + workspace editor):
 *  - form bertanda data-ajax, tanpa form bersarang
 *  - POST/DELETE dengan Accept: application/json -> 200 {ok, message}
 *  - validasi gagal -> 422 {message, errors}
 *  - komunikasi via aplikasi (in-app notification) tetap dibuat
 *  - tanpa header AJAX -> tetap redirect (regression)
 */
class ReviewAjaxTest extends TestCase
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
            'title' => 'Naskah Uji AJAX',
            'cover_letter' => 'Hormat kami',
            'conflict_of_interest' => 'Tidak ada',
            'metadata' => [
                'id_title' => 'Naskah Uji AJAX',
                'id_abstract' => 'Abstrak uji.',
                'keywords' => ['uji'],
                'license' => 'CC-BY-4.0',
            ],
            'submitted_at' => now(),
        ]);
    }

    private function setupReviewFlow(): array
    {
        $author = $this->user('ajax-author@example.org');
        $eic = $this->user('ajax-eic@example.org', ['eic', 'admin']);
        $reviewer = $this->user('ajax-reviewer@example.org', ['reviewer']);

        $s = $this->makeSubmission($author);

        // editor mulai review (AJAX) lalu assign reviewer internal (AJAX)
        $this->actingAs($eic)->postJson('/editor/submissions/'.$s->reference_no.'/stage', [
            'to_stage' => 'under_review',
        ])->assertOk()->assertJson(['ok' => true]);
        $this->assertSame('under_review', $s->fresh()->stage);

        $this->actingAs($eic)->postJson('/editor/submissions/'.$s->reference_no.'/reviewers/assign', [
            'reviewer_ids' => [$reviewer->id],
        ])->assertOk()->assertJson(['ok' => true]);

        $review = Review::where('submission_id', $s->id)->first();
        $this->assertNotNull($review);
        $this->assertSame('invited', $review->status);

        return [$author, $eic, $reviewer, $s];
    }

    public function test_review_form_uses_ajax_without_nested_form(): void
    {
        $this->seed();
        [, , $reviewer, $s] = $this->setupReviewFlow();

        $html = $this->actingAs($reviewer)->get('/review/'.$s->reference_no)->assertOk()->getContent();

        $this->assertStringContainsString('id="reviewForm"', $html);
        $this->assertStringContainsString('data-ajax', $html);
        $this->assertStringContainsString('form="reviewForm"', $html);

        // form review tidak boleh memuat form lain di dalamnya
        // (bug lama: form tolak bersarang -> parser menutup form luar, tombol submit yatim)
        $start = strpos($html, '<form id="reviewForm"');
        $end = strpos($html, '</form>', $start);
        $segment = substr($html, $start, $end - $start);
        $this->assertSame(1, substr_count($segment, '<form'), 'form review tidak boleh bersarang');
    }

    public function test_reviewer_submits_review_via_ajax(): void
    {
        $this->seed();
        [, $eic, $reviewer, $s] = $this->setupReviewFlow();
        $review = Review::where('submission_id', $s->id)->first();

        $this->actingAs($reviewer)
            ->postJson('/review/'.$s->reference_no, [
                'recommended_decision' => 'minor_revise',
                'rating_novelty' => 4,
                'rating_methodology' => 3,
                'rating_clarity' => 4,
                'rating_contribution' => 4,
                'comments_to_author' => 'Perbaiki metodologi.',
                'comments_to_editor' => 'Disarankan minor revise.',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => 'Review terkirim. Terima kasih!']);

        $this->assertSame('submitted', $review->fresh()->status);

        // komunikasi via aplikasi: notifikasi in-app ke editor
        $this->assertTrue(
            Notification::where('user_id', $eic->id)->where('type', 'review_submitted')->exists(),
            'editor harus menerima notifikasi in-app'
        );
    }

    public function test_editor_saves_decision_via_ajax(): void
    {
        $this->seed();
        [$author, $eic, $reviewer, $s] = $this->setupReviewFlow();

        $this->actingAs($reviewer)->postJson('/review/'.$s->reference_no, [
            'recommended_decision' => 'accept',
            'comments_to_author' => 'Layak terbit.',
        ])->assertOk();

        $this->actingAs($eic)
            ->postJson('/editor/submissions/'.$s->reference_no.'/decision', [
                'type' => 'accept',
                'comment_to_author' => 'Diterima dengan senang hati.',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame('accepted', $s->fresh()->stage);

        // komunikasi via aplikasi: notifikasi in-app ke penulis
        $this->assertTrue(
            Notification::where('user_id', $author->id)->where('type', 'decision_made')->exists(),
            'penulis harus menerima notifikasi in-app keputusan'
        );
    }

    public function test_validation_error_returns_422_json(): void
    {
        $this->seed();
        [, $eic, , $s] = $this->setupReviewFlow();

        $response = $this->actingAs($eic)
            ->postJson('/editor/submissions/'.$s->reference_no.'/decision', [])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);

        $errors = json_decode($response->getContent(), true)['errors'];
        $this->assertArrayHasKey('type', $errors);
        $this->assertArrayHasKey('comment_to_author', $errors);
    }

    public function test_editor_message_to_reviewer_creates_in_app_notification(): void
    {
        $this->seed();
        [, $eic, $reviewer, $s] = $this->setupReviewFlow();

        $this->actingAs($eic)
            ->postJson('/editor/submissions/'.$s->reference_no.'/messages', [
                'recipient_type' => 'reviewer',
                'subject' => 'Permintaan klarifikasi',
                'body' => 'Mohon klarifikasi bagian metodologi.',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => 'Pesan terkirim.']);

        $this->assertTrue(
            Notification::where('user_id', $reviewer->id)->where('type', 'editor_message')->exists(),
            'reviewer harus menerima pesan via aplikasi'
        );
    }

    public function test_cancel_reviewer_uses_confirmation_modal(): void
    {
        $this->seed();
        [, , , $s] = $this->setupReviewFlow();
        $review = Review::where('submission_id', $s->id)->first();

        $eic = $this->user('ajax-modal-eic@example.org', ['eic']);
        $html = $this->actingAs($eic)->get('/editor/submissions/'.$s->reference_no)->assertOk()->getContent();

        // tombol Batalkan membuka modal, bukan confirm() native
        $this->assertStringContainsString("\$dispatch('open-modal', 'cancel-review-{$review->id}')", $html);
        $this->assertStringNotContainsString('onsubmit="return confirm', $html);

        // modal Alpine terdaftar dengan nama unik per review
        $this->assertStringContainsString("\$event.detail == 'cancel-review-{$review->id}'", $html);
        $this->assertStringContainsString('Batalkan reviewer?', $html);
    }

    public function test_review_queue_links_use_reference_no_not_id(): void
    {
        $this->seed();
        [, , $reviewer, $s] = $this->setupReviewFlow();

        $html = $this->actingAs($reviewer)->get('/review')->assertOk()->getContent();

        // link antrian harus memakai reference_no (route key), bukan id
        $this->assertStringContainsString('/review/'.$s->reference_no, $html);
        $this->assertStringNotContainsString('/review/'.$s->id.'"', $html);

        // dan link tersebut benar-benar terbuka (bukan 404)
        $this->actingAs($reviewer)->get('/review/'.$s->reference_no)->assertOk();
    }

    public function test_non_ajax_request_still_redirects(): void
    {
        $this->seed();
        $author = $this->user('ajax2-author@example.org');
        $eic = $this->user('ajax2-eic@example.org', ['eic']);
        $s = $this->makeSubmission($author);

        $this->actingAs($eic)
            ->post('/editor/submissions/'.$s->reference_no.'/stage', ['to_stage' => 'under_review'])
            ->assertRedirect();

        $this->assertSame('under_review', $s->fresh()->stage);
    }
}
