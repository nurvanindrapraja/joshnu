<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages(): void
    {
        $this->seed();
        foreach (['/', '/about', '/current-issue', '/archive', '/archive/vol3-no2-2025', '/articles', '/announcements', '/announcements/1', '/article/joshnu-v3i2-001', '/article/joshnu-v3i2-001/citations?format=apa', '/article/joshnu-v3i2-001/related', '/sitemap.xml', '/login', '/register', '/up'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/oai?verb=ListRecords&metadataPrefix=oai_dc')->assertOk()->assertSee('<ListRecords>', false);
    }

    public function test_author_workspace(): void
    {
        $this->seed();
        $author = User::where('email', 'arief@author.example.org')->first();

        $this->actingAs($author)
            ->get('/account/submissions')
            ->assertOk()
            ->assertSee('JOSHNU-');

        // buat naskah baru
        $resp = $this->actingAs($author)->post('/account/submissions', [
            'section_id' => \App\Models\Section::first()->id,
            'title_id' => 'Judul Uji',
            'title_en' => 'Title Test',
            'abstract_id' => 'Abstrak uji.',
            'keywords' => 'kunci, kata',
            'license' => 'CC-BY-4.0',
            'cover_letter' => 'Hormat kami.',
            'conflict_of_interest' => 'Tidak ada',
        ]);
        $resp->assertRedirect();

        // daftar naskah
        $this->actingAs($author)->get('/account/submissions')->assertOk();
    }

    public function test_reviewer_workflow(): void
    {
        $this->seed();
        $reviewer = User::where('email', 'rina@ugm.example.ac.id')->first();

        $this->actingAs($reviewer)->get('/review')->assertOk();
    }

    public function test_editor_workflow(): void
    {
        $this->seed();
        $editor = User::where('email', 'editor@joshnu.example.org')->first();

        $this->actingAs($editor)->get('/editor')->assertOk();
        $this->actingAs($editor)->get('/editor/submissions')->assertOk();
        $this->actingAs($editor)->get('/editor/submissions/JOSHNU-'.now()->year.'-0042')->assertOk();
        $this->actingAs($editor)->get('/editor/issues')->assertOk();
        $this->actingAs($editor)->get('/editor/reviewers')->assertOk();
        $this->actingAs($editor)->get('/editor/announcements')->assertOk();
    }

    public function test_production_workspace(): void
    {
        $this->seed();
        $prod = User::where('email', 'produksi@joshnu.example.org')->first();

        $this->actingAs($prod)->get('/production')->assertOk();
    }

    public function test_admin_workspace(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@joshnu.example.org')->first();

        $this->actingAs($admin)->get('/admin/settings')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/payments')->assertOk();
        $this->actingAs($admin)->get('/admin/reports')->assertOk();
        $this->actingAs($admin)->get('/admin/audit-logs')->assertOk();
    }

    public function test_subscribe_flow(): void
    {
        $this->seed();

        $this->post('/subscribe', ['email' => 'baru@example.com'])
            ->assertStatus(200)
            ->assertSee('baru@example.com');

        $subscriber = \App\Models\Subscriber::where('email', 'baru@example.com')->first();
        $this->assertNotNull($subscriber->token);

        $this->get('/subscribe/confirm?token='.$subscriber->token)
            ->assertOk();
        $this->assertTrue($subscriber->fresh()->is_confirmed);

        // unsubscribe
        $this->get('/unsubscribe?token='.$subscriber->token)->assertOk();
        $this->assertNull(\App\Models\Subscriber::where('email', 'baru@example.com')->first());
    }

    public function test_role_guard(): void
    {
        $this->seed();
        $author = User::where('email', 'arief@author.example.org')->first();

        // guest redirect ke login
        $this->get('/editor')->assertRedirect('/login');
        // author tidak boleh buka /editor
        $this->actingAs($author)->get('/editor')->assertForbidden();
    }
}
