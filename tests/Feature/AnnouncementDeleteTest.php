<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Hapus pengumuman: konfirmasi popup modal (bukan confirm() native) + AJAX.
 */
class AnnouncementDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function eic(): User
    {
        $u = User::firstOrCreate(['email' => 'ann-eic@example.org'], [
            'name' => 'EIC Ann',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);
        $u->roles()->firstOrCreate(['role' => 'eic']);

        return $u;
    }

    private function announcement(): Announcement
    {
        return Announcement::create([
            'title' => 'Pengumuman Uji',
            'body' => 'Isi pengumuman uji.',
            'type' => 'news',
            'starts_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_delete_uses_confirmation_modal(): void
    {
        Storage::fake('local');
        $this->seed();
        $a = $this->announcement();

        $html = $this->actingAs($this->eic())->get('/editor/announcements')->assertOk()->getContent();

        // tombol Hapus membuka modal, bukan confirm() native
        $this->assertStringContainsString("\$dispatch('open-modal', 'delete-announcement-{$a->id}')", $html);
        $this->assertStringNotContainsString("onsubmit=\"return confirm", $html);

        // modal berisi form DELETE + data-ajax
        $this->assertStringContainsString("\$event.detail == 'delete-announcement-{$a->id}'", $html);
        $this->assertStringContainsString('Hapus pengumuman?', $html);
    }

    public function test_delete_via_ajax(): void
    {
        Storage::fake('local');
        $this->seed();
        $a = $this->announcement();

        $this->actingAs($this->eic())
            ->deleteJson('/editor/announcements/'.$a->id)
            ->assertOk()
            ->assertJson(['ok' => true, 'message' => 'Pengumuman "Pengumuman Uji" dihapus (arsip).']);

        $this->assertSoftDeleted('announcements', ['id' => $a->id]);
    }

    public function test_delete_non_ajax_still_redirects(): void
    {
        Storage::fake('local');
        $this->seed();
        $a = $this->announcement();

        $this->actingAs($this->eic())
            ->delete('/editor/announcements/'.$a->id)
            ->assertRedirect();

        $this->assertSoftDeleted('announcements', ['id' => $a->id]);
    }
}
