<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Hapus user (admin): hanya user tanpa riwayat proses dalam jurnal,
 * konfirmasi popup modal, AJAX + toast.
 */
class AdminUserDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::firstOrCreate(['email' => 'del-admin@example.org'], [
            'name' => 'Admin Del',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);
        $u->roles()->firstOrCreate(['role' => 'admin']);

        return $u;
    }

    private function freshUser(string $email, string $name): User
    {
        $u = User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);
        $u->roles()->create(['role' => 'author']);

        return $u;
    }

    public function test_user_without_history_can_be_deleted_via_ajax(): void
    {
        Storage::fake('local');
        $this->seed();
        $target = $this->freshUser('baru@example.org', 'User Baru');

        $this->actingAs($this->admin())
            ->deleteJson('/admin/users/'.$target->id)
            ->assertOk()
            ->assertJson(['message' => 'User User Baru dihapus.']);

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('user_roles', ['user_id' => $target->id]);
    }

    public function test_user_with_submissions_cannot_be_deleted(): void
    {
        Storage::fake('local');
        $this->seed();
        $author = User::where('email', 'arief@author.example.org')->first();
        $this->assertGreaterThan(0, $author->submissions()->count());

        $this->actingAs($this->admin())
            ->deleteJson('/admin/users/'.$author->id)
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $author->id]);
    }

    public function test_user_with_reviews_cannot_be_deleted(): void
    {
        Storage::fake('local');
        $this->seed();
        $reviewer = User::where('email', 'rina@ugm.example.ac.id')->first();
        $this->assertGreaterThan(0, $reviewer->reviews()->count());

        $this->actingAs($this->admin())
            ->deleteJson('/admin/users/'.$reviewer->id)
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Review dikerjakan'));

        $this->assertDatabaseHas('users', ['id' => $reviewer->id]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        Storage::fake('local');
        $this->seed();
        $admin = User::where('email', 'admin@joshnu.example.org')->first();

        // admin kedua agar proteksi "admin terakhir" tidak terpicu lebih dulu
        $other = User::create(['name' => 'Admin Kedua', 'email' => 'admin2@example.org', 'password' => 'secret', 'email_verified_at' => now()]);
        $other->roles()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->deleteJson('/admin/users/'.$admin->id)
            ->assertStatus(422)
            ->assertJson(['message' => 'Anda tidak dapat menghapus akun sendiri.']);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_last_admin_cannot_be_deleted(): void
    {
        // tanpa seed: satu-satunya admin, tanpa riwayat
        $only = $this->freshUser('satu-admin@example.org', 'Satu Admin');
        $only->roles()->delete();
        $only->roles()->create(['role' => 'admin']);

        $this->actingAs($only)
            ->deleteJson('/admin/users/'.$only->id)
            ->assertStatus(422)
            ->assertJson(['message' => 'Admin terakhir tidak dapat dihapus.']);

        $this->assertDatabaseHas('users', ['id' => $only->id]);
    }

    public function test_index_shows_delete_button_state(): void
    {
        Storage::fake('local');
        $this->seed();
        $fresh = $this->freshUser('fresh@example.org', 'Fresh User');
        // role tambahan agar tetap di tab Staff (bukan author-only)
        $fresh->roles()->create(['role' => 'reviewer']);
        $author = User::where('email', 'arief@author.example.org')->first();

        $html = $this->actingAs($this->admin())->get('/admin/users')->assertOk()->getContent();

        // modal konfirmasi hapus ada
        $this->assertStringContainsString("\$event.detail == 'user-delete-modal'", $html);
        $this->assertStringContainsString('Hapus user?', $html);

        // user tanpa riwayat: tombol aktif
        $this->assertStringContainsString(":disabled=\"saving || false\"", $html);
        // user dengan riwayat: tombol nonaktif + alasan di tooltip
        $this->assertStringContainsString(':disabled="saving || true"', $html);
        $this->assertStringContainsString('Tidak dapat dihapus:', $html);

        // data blocker ikut dikirim ke modal (@js merender JSON dengan escape \u0022)
        $this->assertStringContainsString('\u0022id\u0022:'.$fresh->id.',', $html);

        // user author-only kini di tab "Penulis", bukan di tab staff
        $this->assertStringNotContainsString('\u0022id\u0022:'.$author->id.',', $html);
        $htmlAuthors = $this->actingAs($this->admin())->get('/admin/users?tab=authors')->assertOk()->getContent();
        $this->assertStringContainsString('\u0022id\u0022:'.$author->id.',', $htmlAuthors);
    }

    public function test_delete_non_ajax_still_redirects(): void
    {
        Storage::fake('local');
        $this->seed();
        $target = $this->freshUser('nonajax@example.org', 'Non Ajax');

        $this->actingAs($this->admin())
            ->delete('/admin/users/'.$target->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }
}
