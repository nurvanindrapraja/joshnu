<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationUrlTest extends TestCase
{
    use RefreshDatabase;

    private function notificationWithUrl(string $url): Notification
    {
        $user = User::factory()->create();

        Notification::send($user->id, 'decision_made', [
            'submission_ref' => 'JOSHNU-2026-0001',
            'title' => 'Naskah Uji',
            'url' => $url,
        ]);

        return Notification::latest('id')->first();
    }

    public function test_stored_localhost_url_rewritten_to_current_host(): void
    {
        $n = $this->notificationWithUrl('http://127.0.0.1:8000/account/submissions/JOSHNU-2026-0001/payment');

        $this->actingAs($n->user)->get('/notifications')->assertOk();

        // data mentah di DB tetap lama
        $this->assertStringContainsString('127.0.0.1', $n->fresh()->data['url']);
        // accessor menulis ulang ke host permintaan berjalan
        $this->assertSame(
            'http://localhost:8000/account/submissions/JOSHNU-2026-0001/payment',
            $n->fresh()->url
        );
    }

    public function test_url_matches_current_host_unchanged(): void
    {
        $n = $this->notificationWithUrl('http://localhost:8000/editor/submissions/JOSHNU-2026-0001');

        $this->assertSame(
            'http://localhost:8000/editor/submissions/JOSHNU-2026-0001',
            $n->fresh()->url
        );
    }

    public function test_relative_url_kept_as_is(): void
    {
        $n = $this->notificationWithUrl('/editor/submissions/JOSHNU-2026-0001');

        $this->assertSame('/editor/submissions/JOSHNU-2026-0001', $n->fresh()->url);
    }

    public function test_null_url_returns_null(): void
    {
        $user = User::factory()->create();
        Notification::send($user->id, 'decision_made', ['submission_ref' => 'X']);
        $n = Notification::latest('id')->first();

        $this->assertNull($n->url);
    }

    public function test_notifications_page_renders_rewritten_links(): void
    {
        $n = $this->notificationWithUrl('http://127.0.0.1:8000/account/submissions/JOSHNU-2026-0001/payment');
        $user = $n->user;

        $html = $this->actingAs($user)->get('/notifications')->assertOk()->getContent();

        $this->assertStringNotContainsString('127.0.0.1', $html);
        $this->assertStringContainsString('href="http://localhost:8000/account/submissions/JOSHNU-2026-0001/payment"', $html);
    }
}
