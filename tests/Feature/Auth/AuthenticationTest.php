<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        // form login dikirim via AJAX + navigasi penuh setelah sukses
        $response->assertSee('data-ajax-redirect', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_login_via_ajax_returns_json_with_redirect(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect', route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_unverified_login_via_ajax_returns_422_unverified(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('unverified', true)
            ->assertJsonPath('email', $user->email)
            ->assertJsonStructure(['ok', 'unverified', 'email', 'message']);

        // tetap guest: login ditolak
        $this->assertGuest();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_get_logout_redirects_back_with_notice(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from('/account/submissions')
            ->get('/logout');

        // tidak error 405: kembali ke halaman sebelumnya + pemberitahuan
        $response->assertRedirect('/account/submissions');
        $response->assertSessionHas('error');
        // tetap login: logout hanya via POST
        $this->assertAuthenticated();
    }

    public function test_unverified_user_cannot_login(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        // login ditolak: tetap guest + flash 'unverified' berisi email
        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('unverified', $user->email);
    }
}
