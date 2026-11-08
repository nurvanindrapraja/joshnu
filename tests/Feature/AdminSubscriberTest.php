<?php

namespace Tests\Feature;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman admin untuk melihat email yang berlangganan JOSHNU.
 */
class AdminSubscriberTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::firstOrCreate(['email' => 'sub-admin@example.org'], [
            'name' => 'Sub Admin',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);
        $u->roles()->firstOrCreate(['role' => 'admin']);

        return $u;
    }

    private function editor(): User
    {
        $u = User::firstOrCreate(['email' => 'sub-editor@example.org'], [
            'name' => 'Sub Editor',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);
        $u->roles()->firstOrCreate(['role' => 'eic']);

        return $u;
    }

    private function subscriber(string $email, bool $confirmed = true): Subscriber
    {
        return Subscriber::firstOrCreate(
            ['email' => $email],
            ['token' => Subscriber::newToken(), 'is_confirmed' => $confirmed]
        );
    }

    public function test_admin_sees_subscriber_menu_and_list(): void
    {
        $admin = $this->admin();
        $this->subscriber('baca@contoh.org', true);
        $this->subscriber('nanti@contoh.org', false);

        $res = $this->actingAs($admin)->get('/admin/subscribers');
        $res->assertOk();
        $res->assertSee('baca@contoh.org');
        $res->assertSee('nanti@contoh.org');
        $res->assertSee('Terkonfirmasi');
        $res->assertSee('Menunggu');
        // kartu statistik
        $res->assertSee('Semua');
        $res->assertSee('Menunggu Konfirmasi');
    }

    public function test_subscribers_appear_in_admin_sidebar(): void
    {
        $admin = $this->admin();

        $res = $this->actingAs($admin)->get(route('admin.users.index'));
        $res->assertOk();
        $res->assertSee(route('admin.subscribers.index'));
        $res->assertSee('Subscribers');
    }

    public function test_search_uses_ajax_live_markup(): void
    {
        $admin = $this->admin();
        $this->subscriber('satu@contoh.org');

        $res = $this->actingAs($admin)->get('/admin/subscribers');
        $res->assertOk();
        // form live-search AJAX + area hasil + tombol reset (bukan link, bukan submit)
        $res->assertSee('data-ajax-filter', false);
        $res->assertSee('data-ajax-results', false);
        $res->assertSee('data-ajax-reset', false);
        $res->assertSee('type="search"', false);
        // tombol "Cari" sudah dihapus (form murni live-search)
        $res->assertDontSee('Cari</button>');
    }

    public function test_search_filters_by_email(): void
    {
        $admin = $this->admin();
        $this->subscriber('satu@contoh.org');
        $this->subscriber('dua@contoh.org');

        $res = $this->actingAs($admin)->get('/admin/subscribers?q=dua');
        $res->assertOk();
        $res->assertSee('dua@contoh.org');
        $res->assertDontSee('satu@contoh.org');
    }

    public function test_status_filter_confirmed(): void
    {
        $admin = $this->admin();
        $this->subscriber('pasti@contoh.org', true);
        $this->subscriber('belum@contoh.org', false);

        $res = $this->actingAs($admin)->get('/admin/subscribers?status=confirmed');
        $res->assertOk();
        $res->assertSee('pasti@contoh.org');
        $res->assertDontSee('belum@contoh.org');
    }

    public function test_non_admin_cannot_access(): void
    {
        $editor = $this->editor();

        $this->actingAs($editor)->get('/admin/subscribers')->assertForbidden();
    }

    public function test_guest_cannot_access(): void
    {
        $this->get('/admin/subscribers')->assertRedirect(route('login'));
    }
}
