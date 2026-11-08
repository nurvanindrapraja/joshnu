<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified_without_login_and_auto_logs_in(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake([Verified::class]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // diakses TANPA login (user baru daftar, belum pernah login)
        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_expired_signed_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(1), // sudah kedaluwarsa
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->get($verificationUrl)->assertStatus(403);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->get($verificationUrl)->assertStatus(403);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_link_can_be_resent_without_login(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();

        $response = $this->post('/email/resend-verification', ['email' => $user->email]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Mail::assertQueued(VerifyEmail::class);
    }

    public function test_resend_for_unknown_email_does_not_send(): void
    {
        Mail::fake();

        $response = $this->post('/email/resend-verification', ['email' => 'ghost@example.com']);

        // respons tetap sama (anti enumeration), tapi tidak ada email keluar
        $response->assertRedirect();
        $response->assertSessionHas('status');
        Mail::assertNotQueued(VerifyEmail::class);
    }
}
