<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Tab "Penulis" di Users & Roles: akun yang HANYA ber-role author
 * disendirikan dari daftar utama (tab Staff).
 */
class AdminUsersTabTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::firstOrCreate(['email' => 'tab-admin@example.org'], [
            'name' => 'Admin Tab',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);
        $u->roles()->firstOrCreate(['role' => 'admin']);

        return $u;
    }

    private function makeUser(string $name, array $roles): User
    {
        $u = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.org',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);
        foreach ($roles as $r) {
            $u->roles()->create(['role' => $r]);
        }

        return $u;
    }

    public function test_author_only_users_separated_into_authors_tab(): void
    {
        Storage::fake('local');
        $this->seed();

        $authorOnly = $this->makeUser('Penulis Murni AA', ['author']);
        $staffUser = $this->makeUser('Reviewer Biasa BB', ['reviewer']);
        $both = $this->makeUser('Penulis Reviewer CC', ['author', 'reviewer']);
        $noRole = $this->makeUser('Tanpa Role DD', []);

        $admin = $this->admin();

        // tab staff (default): punya role selain author
        $html = $this->actingAs($admin)->get('/admin/users')->assertOk()->getContent();
        $this->assertStringContainsString('Reviewer Biasa BB', $html);
        $this->assertStringContainsString('Penulis Reviewer CC', $html); // author+reviewer tetap di staff
        $this->assertStringNotContainsString('Tanpa Role DD', $html);
        $this->assertStringNotContainsString('Penulis Murni AA', $html);

        // tab penulis: author-only + user tanpa role (baru daftar)
        $html = $this->actingAs($admin)->get('/admin/users?tab=authors')->assertOk()->getContent();
        $this->assertStringContainsString('Penulis Murni AA', $html);
        $this->assertStringContainsString('Tanpa Role DD', $html); // baru daftar, belum ada tulisan
        $this->assertStringNotContainsString('Reviewer Biasa BB', $html);
        $this->assertStringNotContainsString('Penulis Reviewer CC', $html);
    }

    public function test_tabs_links_and_counts_rendered(): void
    {
        Storage::fake('local');
        $this->seed();
        $this->makeUser('Penulis Tab EE', ['author']);

        $html = $this->actingAs($this->admin())->get('/admin/users')->assertOk()->getContent();

        $this->assertStringContainsString('tab=authors', $html);
        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('aria-selected="true"', $html);
    }

    public function test_search_filter_works_within_authors_tab(): void
    {
        Storage::fake('local');
        $this->seed();
        $this->makeUser('Penulis Zebra FF', ['author']);
        $this->makeUser('Penulis Alpha GG', ['author']);

        $html = $this->actingAs($this->admin())
            ->get('/admin/users?tab=authors&q=Zebra')
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Penulis Zebra FF', $html);
        $this->assertStringNotContainsString('Penulis Alpha GG', $html);
    }
}
