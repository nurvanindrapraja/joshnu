<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Publication;
use App\Models\Section;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Aksi production (publish/unpublish artikel) memakai modal konfirmasi,
 * bukan confirm() native.
 */
class ProductionConfirmModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_actions_use_confirmation_modals(): void
    {
        Storage::fake('local');
        $this->seed();

        $eic = User::firstOrCreate(['email' => 'prod-eic@example.org'], [
            'name' => 'EIC Prod',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);
        $eic->roles()->firstOrCreate(['role' => 'eic']);

        // artikel ber-stage scheduled (muncul tombol Publish)
        $author = User::where('email', 'arief@author.example.org')->first();
        $sub = Submission::create([
            'reference_no' => Submission::nextReferenceNo(),
            'author_id' => $author->id,
            'section_id' => Section::first()->id,
            'stage' => 'scheduled',
            'current_round' => 1,
            'title' => 'Naskah Uji Production',
            'cover_letter' => 'Hormat kami',
            'conflict_of_interest' => 'Tidak ada',
            'metadata' => [
                'id_title' => 'Naskah Uji Production',
                'id_abstract' => 'Abstrak uji.',
                'keywords' => ['uji'],
                'license' => 'CC-BY-4.0',
            ],
            'submitted_at' => now(),
        ]);
        $issue = Issue::create(['volume' => 99, 'number' => 9, 'year' => 2026]);
        $pub = Publication::create([
            'submission_id' => $sub->id,
            'issue_id' => $issue->id,
            'section_id' => $sub->section_id,
            'slug' => 'joshnu-uji-production-'.uniqid(),
            'published_at' => null,
            'position' => 1,
            'doi' => '10.5281/joshnu.uji.1',
            'doi_status' => 'pending',
            'article_status' => 'active',
        ]);

        $html = $this->actingAs($eic)->get('/production')->assertOk()->getContent();

        // tidak ada confirm() native tersisa
        $this->assertStringNotContainsString('onsubmit="return confirm', $html);

        // tombol Publish membuka modal konfirmasi
        $this->assertStringContainsString("'confirm-publish-article-{$pub->id}'", $html);
        $this->assertStringContainsString('Publikasikan artikel?', $html);

        // artikel terbit (seed) punya modal konfirmasi unpublish
        $published = Publication::whereNotNull('published_at')->first();
        $this->assertNotNull($published);
        $this->assertStringContainsString("'confirm-unpublish-article-{$published->id}'", $html);
        $this->assertStringContainsString('Unpublish artikel?', $html);
    }
}
