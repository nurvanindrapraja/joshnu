<?php

namespace Tests\Feature;

use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParamRouteCrawlTest extends TestCase
{
    use RefreshDatabase;

    public function test_parameterized_pages_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@joshnu.example.org')->first();
        $author = User::where('email', 'arief@author.example.org')->first();
        $reviewer = User::where('email', 'rina@ugm.example.ac.id')->first();
        $prod = User::where('email', 'produksi@joshnu.example.org')->first();

        $subs = Submission::all();
        $failures = [];

        $visit = function (string $label, $user, string $uri) use (&$failures) {
            $res = $this->actingAs($user)->get($uri);
            $code = $res->getStatusCode();
            if ($code >= 500) {
                $failures[] = $label.' '.$uri.' => '.$code.' '.($res->exception ? $res->exception->getMessage() : '');
            }
        };

        // admin: semua halaman detail
        foreach ($subs as $s) {
            $visit('admin', $admin, '/editor/submissions/'.$s->reference_no);
            $visit('admin', $admin, '/editor/submissions/'.$s->reference_no.'/audit');
            $visit('admin', $admin, '/editor/issues/'.$s->id.'/edit');
        }
        $visit('admin', $admin, '/editor/issues/create');
        $visit('admin', $admin, '/editor/announcements');
        $visit('admin', $admin, '/editor/reviewers');
        $visit('admin', $admin, '/production');
        $visit('admin', $admin, '/admin/users');
        $visit('admin', $admin, '/admin/settings');
        $visit('admin', $admin, '/admin/payments');
        $visit('admin', $admin, '/admin/reports?from='.now()->subMonths(3)->format('Y-m-d'));
        $visit('admin', $admin, '/admin/audit-logs');
        $visit('admin', $admin, '/notifications');

        // author: halaman naskah miliknya
        $mine = $subs->firstWhere('author_id', $author->id);
        if ($mine) {
            $visit('author', $author, '/account/submissions/'.$mine->reference_no);
            $visit('author', $author, '/account/submissions/'.$mine->reference_no.'/edit');
            $visit('author', $author, '/account/submissions/'.$mine->reference_no.'/files');
            $visit('author', $author, '/account/submissions/'.$mine->reference_no.'/revisions');
            $visit('author', $author, '/account/submissions/'.$mine->reference_no.'/payment');
        }
        $visit('author', $author, '/account/submissions/new');

        // reviewer: halaman review
        foreach ($subs as $s) {
            if ($s->reviews()->where('reviewer_id', $reviewer->id)->exists()) {
                $visit('reviewer', $reviewer, '/review/'.$s->reference_no);
            }
        }

        // production
        $visit('production', $prod, '/production');

        $this->assertEmpty($failures, 'Halaman error: '.implode(' | ', $failures));
    }
}
