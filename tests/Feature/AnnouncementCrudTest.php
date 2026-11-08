<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * CRUD pengumuman (editor): form modal + AJAX + poster dengan preview,
 * progres & toast ditangani client-side oleh ajax-actions.js.
 */
class AnnouncementCrudTest extends TestCase
{
    use RefreshDatabase;

    private function eic(): User
    {
        $u = User::firstOrCreate(['email' => 'ann-crud@example.org'], [
            'name' => 'EIC Ann CRUD',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);
        $u->roles()->firstOrCreate(['role' => 'eic']);

        return $u;
    }

    public function test_index_shows_create_modal_and_form_fields(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed();

        $html = $this->actingAs($this->eic())->get('/editor/announcements')->assertOk()->getContent();

        // tombol buat + modal form
        $this->assertStringContainsString("Buat Pengumuman", $html);
        $this->assertStringContainsString("\$event.detail == 'create-announcement'", $html);
        $this->assertStringContainsString('id="title-create"', $html);
        $this->assertStringContainsString('id="poster-create"', $html);

        // preview poster (Alpine + object URL)
        $this->assertStringContainsString('setPoster', $html);
        $this->assertStringContainsString('URL.createObjectURL', $html);
        $this->assertStringContainsString('Belum ada poster', $html);

        // form modal memakai AJAX
        $this->assertStringContainsString('data-ajax', $html);
    }

    public function test_create_via_ajax(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed();

        $this->actingAs($this->eic())
            ->postJson('/editor/announcements', [
                'title' => 'CFP Vol 6',
                'body' => 'Naskah dapat dikirim hingga 31 Agustus.',
                'type' => 'cfp',
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addMonth()->toDateString(),
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => 'Pengumuman "CFP Vol 6" dibuat.']);

        $this->assertDatabaseHas('announcements', ['title' => 'CFP Vol 6', 'type' => 'cfp']);
    }

    public function test_create_with_poster_stores_to_public_disk_and_previews(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed();

        $poster = File::fake()->image('poster-cfp.jpg', 800, 400);

        $this->actingAs($this->eic())
            ->post('/editor/announcements', [
                'title' => 'CFP Poster',
                'body' => 'Lihat poster.',
                'type' => 'cfp',
                'starts_at' => now()->toDateString(),
                'poster' => $poster,
            ])
            ->assertRedirect(route('editor.announcements.index'));

        $a = Announcement::where('title', 'CFP Poster')->first();
        $this->assertNotNull($a);
        $this->assertNotNull($a->poster_path);
        $this->assertTrue(Storage::disk('public')->exists($a->poster_path));

        // URL poster dapat diakses via /storage
        $this->assertSame(url('storage/'.$a->poster_path), $a->poster_url);

        // modal edit memuat preview poster (coverUrl berisi URL /storage)
        $html = $this->actingAs($this->eic())->get('/editor/announcements')->assertOk()->getContent();
        $needle = "detail == 'edit-announcement-{$a->id}'";
        $pos = strpos($html, $needle);
        $this->assertNotFalse($pos, 'modal edit tidak ditemukan');
        $chunk = str_replace('\\/', '/', substr($html, $pos, 12000));
        $this->assertStringContainsString('storage/'.$a->poster_path, $chunk);
        // daftar menampilkan thumbnail poster
        $this->assertStringContainsString('src="'.url('storage/'.$a->poster_path).'"', $html);
    }

    public function test_update_via_ajax_replaces_poster(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed();

        $oldPoster = File::fake()->image('poster-lama.jpg', 400, 300);
        $this->actingAs($this->eic())->post('/editor/announcements', [
            'title' => 'Sebelum', 'body' => 'Isi lama.', 'type' => 'news',
            'starts_at' => now()->toDateString(), 'poster' => $oldPoster,
        ]);
        $a = Announcement::where('title', 'Sebelum')->first();
        $this->assertNotNull($a->poster_path);
        $oldPath = $a->poster_path;

        $newPoster = File::fake()->image('poster-baru.jpg', 400, 300);
        $this->actingAs($this->eic())
            ->putJson('/editor/announcements/'.$a->id, [
                'title' => 'Sesudah',
                'body' => 'Isi baru.',
                'type' => 'news',
                'starts_at' => now()->toDateString(),
                'poster' => $newPoster,
            ])
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => 'Pengumuman "Sesudah" diperbarui.']);

        $a->refresh();
        $this->assertSame('Sesudah', $a->title);
        $this->assertNotSame($oldPath, $a->poster_path);
        $this->assertTrue(Storage::disk('public')->exists($a->poster_path));
        $this->assertFalse(Storage::disk('public')->exists($oldPath));
    }

    public function test_restore_archived_announcement_via_ajax(): void
    {
        Storage::fake('local');
        $this->seed();

        $a = Announcement::create([
            'title' => 'Arsip Uji', 'body' => 'Isi arsip.', 'type' => 'news',
            'starts_at' => now(), 'is_active' => true,
        ]);
        $a->delete();
        $this->assertSoftDeleted('announcements', ['id' => $a->id]);

        $this->actingAs($this->eic())
            ->postJson('/editor/announcements/'.$a->id.'/restore')
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => 'Pengumuman "Arsip Uji" dipulihkan.']);

        $this->assertNotSoftDeleted('announcements', ['id' => $a->id]);
    }

    public function test_second_destroy_is_permanent(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed();

        $a = Announcement::create([
            'title' => 'Permanen Uji', 'body' => 'Isi.', 'type' => 'news',
            'starts_at' => now(), 'is_active' => true,
        ]);
        $this->actingAs($this->eic())->deleteJson('/editor/announcements/'.$a->id)->assertOk();
        $this->assertSoftDeleted('announcements', ['id' => $a->id]);

        $this->actingAs($this->eic())
            ->deleteJson('/editor/announcements/'.$a->id)
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => 'Pengumuman "Permanen Uji" dihapus permanen.']);

        $this->assertDatabaseMissing('announcements', ['id' => $a->id]);
    }

    public function test_validation_error_returns_422_json(): void
    {
        Storage::fake('local');
        $this->seed();

        $this->actingAs($this->eic())
            ->postJson('/editor/announcements', ['title' => '', 'body' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'body']);
    }
}
