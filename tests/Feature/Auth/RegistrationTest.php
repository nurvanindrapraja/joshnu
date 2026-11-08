<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        // form register dikirim via AJAX + navigasi penuh setelah sukses
        $response->assertSee('data-ajax-redirect', false);
    }

    public function test_new_users_register_but_cannot_login_before_verification(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // tidak langsung login; kembali ke halaman login dengan pesan status
        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        // akun dibuat dalam kondisi belum terverifikasi
        $user = \App\Models\User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->hasVerifiedEmail());

        // email verifikasi terkirim (queued) PERSIS SEKALI
        Mail::assertQueued(VerifyEmail::class, 1);
    }

    public function test_duplicate_email_rejected_without_verification_mail(): void
    {
        Mail::fake();
        \App\Models\User::factory()->create(['email' => 'dup@example.com']);

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'dup@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');

        Mail::assertNotQueued(VerifyEmail::class);
    }

    public function test_register_via_ajax_returns_json_with_redirect_to_login(): void
    {
        Mail::fake();

        $response = $this->postJson('/register', [
            'name' => 'Ajax User',
            'email' => 'ajax@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('redirect', route('login'))
            ->assertJsonStructure(['ok', 'message', 'redirect']);

        // tidak login; akun dibuat belum terverifikasi; email verifikasi dikirim sekali
        $this->assertGuest();
        $user = \App\Models\User::where('email', 'ajax@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->hasVerifiedEmail());
        Mail::assertQueued(VerifyEmail::class, 1);
    }

    public function test_register_via_ajax_validation_error_returns_422(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Ajax User',
            'email' => 'bukan-email',
            'password' => 'pendek',
            'password_confirmation' => 'beda',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);

        $this->assertDatabaseCount('users', 0);
    }
}
