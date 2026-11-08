<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Issue management (editor): form modal + AJAX, filter tema/volume/tahun,
 * aksi icon (lihat/unpublish/edit/publish), toast via JSON {ok, message}.
 */
class IssueManagementTest extends TestCase
{
    use RefreshDatabase;

    private function eic(): User
    {
        $u = User::firstOrCreate(['email' => 'issue-eic@example.org'], [
            'name' => 'EIC Issue',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);
        $u->roles()->firstOrCreate(['role' => 'eic']);

        return $u;
    }

    private function issue(array $attrs = []): Issue
    {
        return Issue::create(array_merge([
            'volume' => 1,
            'number' => 1,
            'year' => 2026,
            'title' => null,
            'published_at' => null,
        ], $attrs));
    }

    public function test_index_has_filter_modals_and_icon_actions(): void
    {
        Storage::fake('local');
        $this->seed();
        $this->issue(['volume' => 9, 'number' => 1, 'year' => 2026, 'title' => 'Draft Uji']);
        $this->issue(['volume' => 8, 'number' => 2, 'year' => 2025, 'title' => 'Sudah Terbit', 'published_at' => now()]);

        $html = $this->actingAs($this->eic())->get('/editor/issues')->assertOk()->getContent();

        // filter AJAX di atas tabel
        $this->assertStringContainsString('data-ajax-filter', $html);
        $this->assertStringContainsString('data-ajax-results', $html);
        $this->assertStringContainsString('name="tema"', $html);
        $this->assertStringContainsString('name="volume"', $html);
        $this->assertStringContainsString('name="year"', $html);

        // modal buat issue + tombol pembukanya
        $this->assertStringContainsString("+ Buat Issue", $html);
        $this->assertStringContainsString("\$event.detail == 'create-issue'", $html);

        // cover upload dengan preview (Alpine + object URL)
        $this->assertStringContainsString('setCover', $html);
        $this->assertStringContainsString('URL.createObjectURL', $html);
        $this->assertStringContainsString('Belum ada cover', $html);

        // aksi sebagai icon
        $this->assertStringContainsString('title="Edit"', $html);
        $this->assertStringContainsString('title="Publish"', $html);
        $this->assertStringContainsString('title="Lihat"', $html);
        $this->assertStringContainsString('title="Unpublish"', $html);
        // form edit modal tersedia untuk issue draft
        $this->assertStringContainsString('edit-issue-', $html);

        // reset filter berupa tombol abu-abu (bukan link) dengan atribut data-ajax-reset
        $this->assertStringContainsString('<button type="button" data-ajax-reset', $html);
        $this->assertStringContainsString('bg-gray-100', $html);
        $this->assertStringNotContainsString('<a href="' . route('editor.issues.index') . '" data-ajax-reset', $html);

        // konfirmasi publish/unpublish via modal (bukan confirm() native)
        $this->assertStringNotContainsString('onsubmit="return confirm', $html);
        $this->assertStringContainsString("'confirm-publish-issue-", $html);
        $this->assertStringContainsString("'confirm-unpublish-issue-", $html);
        $this->assertStringContainsString('Publikasikan issue?', $html);
        $this->assertStringContainsString('Unpublish issue?', $html);

        // issue draft punya tombol hapus + modal konfirmasi (issue terbit tidak)
        $this->assertStringContainsString('title="Hapus"', $html);
        $this->assertStringContainsString("'confirm-delete-issue-", $html);
        $this->assertStringContainsString('Hapus issue?', $html);
    }

    public function test_create_issue_via_ajax(): void
    {
        Storage::fake('local');
        $this->seed();

        $this->actingAs($this->eic())
            ->postJson('/editor/issues', [
                'volume' => 2,
                'number' => 3,
                'year' => 2026,
                'title' => 'Tema Uji AJAX',
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => 'Issue Vol. 2 No. 3 (2026) dibuat (draft).']);

        $issue = Issue::where('volume', 2)->where('number', 3)->first();
        $this->assertNotNull($issue);
        $this->assertFalse($issue->isPublished());
    }

    public function test_create_issue_non_ajax_still_redirects(): void
    {
        Storage::fake('local');
        $this->seed();

        $this->actingAs($this->eic())
            ->post('/editor/issues', ['volume' => 3, 'number' => 1, 'year' => 2026])
            ->assertRedirect(route('editor.issues.index'));

        $this->assertDatabaseHas('issues', ['volume' => 3, 'number' => 1]);
    }

    public function test_update_issue_via_ajax(): void
    {
        Storage::fake('local');
        $this->seed();
        $issue = $this->issue(['volume' => 4, 'number' => 1, 'year' => 2026, 'title' => 'Sebelum']);

        $this->actingAs($this->eic())
            ->putJson('/editor/issues/'.$issue->slug, [
                'volume' => 4,
                'number' => 1,
                'year' => 2025,
                'title' => 'Sesudah',
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame('Sesudah', $issue->fresh()->title);
        $this->assertSame(2025, $issue->fresh()->year);
    }

    public function test_published_issue_cannot_be_edited(): void
    {
        Storage::fake('local');
        $this->seed();
        $issue = $this->issue(['volume' => 5, 'number' => 1, 'year' => 2026, 'published_at' => now()]);

        $this->actingAs($this->eic())
            ->putJson('/editor/issues/'.$issue->slug, [
                'volume' => 5,
                'number' => 1,
                'year' => 2024,
            ])
            ->assertStatus(422)
            ->assertJson(['message' => 'Issue yang sudah terbit tidak dapat diedit.']);
    }

    public function test_publish_requires_articles(): void
    {
        Storage::fake('local');
        $this->seed();
        $issue = $this->issue(['volume' => 6, 'number' => 1, 'year' => 2026]);

        $this->actingAs($this->eic())
            ->postJson('/editor/issues/'.$issue->slug.'/publish')
            ->assertStatus(422);

        $this->assertFalse($issue->fresh()->isPublished());
    }

    public function test_unpublish_via_ajax(): void
    {
        Storage::fake('local');
        $this->seed();
        $issue = $this->issue(['volume' => 7, 'number' => 1, 'year' => 2026, 'published_at' => now()]);

        $this->actingAs($this->eic())
            ->postJson('/editor/issues/'.$issue->slug.'/unpublish')
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => 'Issue di-unpublish (kembali draft).']);

        $this->assertFalse($issue->fresh()->isPublished());
    }

    public function test_draft_issue_can_be_deleted_via_ajax(): void
    {
        Storage::fake('local');
        $this->seed();
        $issue = $this->issue(['volume' => 21, 'number' => 1, 'year' => 2026, 'title' => 'Draft Dihapus']);

        $this->actingAs($this->eic())
            ->deleteJson('/editor/issues/'.$issue->slug)
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => 'Issue Vol. 21 No. 1 (2026) dihapus.']);

        $this->assertDatabaseMissing('issues', ['id' => $issue->id]);
    }

    public function test_published_issue_cannot_be_deleted(): void
    {
        Storage::fake('local');
        $this->seed();
        $issue = $this->issue(['volume' => 22, 'number' => 1, 'year' => 2026, 'published_at' => now()]);

        $this->actingAs($this->eic())
            ->deleteJson('/editor/issues/'.$issue->slug)
            ->assertStatus(422)
            ->assertJson(['message' => 'Issue yang sudah terbit tidak dapat dihapus.']);

        $this->assertDatabaseHas('issues', ['id' => $issue->id]);
    }

    public function test_cover_saved_to_public_disk_and_previewed_in_edit_modal(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed();

        $eic = $this->eic();
        $cover = \Illuminate\Http\Testing\File::fake()->image('cover-uji.jpg', 300, 400);

        $this->actingAs($eic)
            ->post('/editor/issues', [
                'volume' => 31,
                'number' => 1,
                'year' => 2026,
                'title' => 'Cover Uji',
                'cover' => $cover,
            ])
            ->assertRedirect(route('editor.issues.index'));

        $issue = Issue::where('volume', 31)->where('number', 1)->first();
        $this->assertNotNull($issue);
        $this->assertNotNull($issue->cover_path);

        // file tersimpan di disk publik sehingga bisa di-serve via /storage
        $this->assertTrue(Storage::disk('public')->exists($issue->cover_path));

        // modal edit memuat preview cover (coverUrl berisi URL /storage)
        $html = $this->actingAs($eic)->get('/editor/issues')->assertOk()->getContent();
        $needle = "detail == 'edit-issue-{$issue->id}'";
        $pos = strpos($html, $needle);
        $this->assertNotFalse($pos, 'modal edit tidak ditemukan');
        $chunk = str_replace('\\/', '/', substr($html, $pos, 12000));
        $this->assertStringContainsString('storage/'.$issue->cover_path, $chunk);
    }

    public function test_draft_issue_with_scheduled_articles_cannot_be_deleted(): void
    {
        Storage::fake('local');
        $this->seed();
        $issue = $this->issue(['volume' => 23, 'number' => 1, 'year' => 2026]);

        $submission = \App\Models\Submission::whereDoesntHave('publication')->first();
        $this->assertNotNull($submission);
        \App\Models\Publication::create([
            'submission_id' => $submission->id,
            'issue_id' => $issue->id,
            'section_id' => $submission->section_id,
            'slug' => 'joshnu-uji-'.uniqid(),
            'position' => 1,
            'doi' => '10.5281/joshnu.uji.2',
            'doi_status' => 'pending',
            'article_status' => 'active',
        ]);

        $this->actingAs($this->eic())
            ->deleteJson('/editor/issues/'.$issue->slug)
            ->assertStatus(422)
            ->assertJson(['message' => 'Issue masih memiliki artikel terjadwal. Unpublish artikelnya terlebih dahulu.']);

        $this->assertDatabaseHas('issues', ['id' => $issue->id]);
    }

    public function test_index_filters_by_tema_volume_year(): void
    {
        Storage::fake('local');
        $this->seed();
        $this->issue(['volume' => 11, 'number' => 1, 'year' => 2024, 'title' => 'Hidup Sehat Halal']);
        $this->issue(['volume' => 12, 'number' => 1, 'year' => 2025, 'title' => 'Kota Hijau Berkelanjutan']);

        $eic = $this->eic();

        // filter tema
        $res = $this->actingAs($eic)->get('/editor/issues?tema=Hidup')->assertOk();
        $res->assertSee('Vol. 11 No. 1 (2024)');
        $res->assertDontSee('Vol. 12 No. 1 (2025)');

        // filter volume
        $res = $this->actingAs($eic)->get('/editor/issues?volume=12')->assertOk();
        $res->assertSee('Vol. 12 No. 1 (2025)');
        $res->assertDontSee('Vol. 11 No. 1 (2024)');

        // filter tahun
        $res = $this->actingAs($eic)->get('/editor/issues?year=2024')->assertOk();
        $res->assertSee('Hidup Sehat Halal');
        $res->assertDontSee('Kota Hijau Berkelanjutan');
    }
}
