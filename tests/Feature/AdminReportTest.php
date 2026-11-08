<?php

namespace Tests\Feature;

use App\Models\ArticleUsage;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Regresi: ArticleUsage memakai $timestamps = false, jadi created_at harus
 * di-cast datetime agar ->format() di laporan tidak crash (string vs Carbon).
 */
class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::firstOrCreate(['email' => 'report-admin@example.org'], [
            'name' => 'Admin Report',
            'password' => 'secret',
            'email_verified_at' => now(),
        ]);
        $u->roles()->firstOrCreate(['role' => 'admin']);

        return $u;
    }

    private function usage(string $type, $when): ArticleUsage
    {
        $pub = Publication::published()->first();
        $this->assertNotNull($pub, 'seed harus punya publikasi terbit');

        return ArticleUsage::create([
            'publication_id' => $pub->id,
            'type' => $type,
            'ip_hash' => hash('sha256', uniqid('', true)),
            'created_at' => $when,
        ]);
    }

    public function test_reports_page_renders_with_usage_data(): void
    {
        Storage::fake('local');
        $this->seed();

        $this->usage('view', now()->subDays(2));
        $this->usage('download', now());

        $this->actingAs($this->admin())
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('Total views');
    }

    public function test_usage_csv_renders(): void
    {
        Storage::fake('local');
        $this->seed();
        $this->usage('view', now());

        $this->actingAs($this->admin())
            ->get('/admin/reports/usage.csv')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8');
    }

    public function test_created_at_is_cast_to_carbon(): void
    {
        $this->seed();
        $u = $this->usage('view', now());
        $u->refresh();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $u->created_at);
        $this->assertSame('2026', $u->created_at->format('Y'));
    }
}
