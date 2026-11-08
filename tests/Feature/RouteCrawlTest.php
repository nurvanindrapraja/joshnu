<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteCrawlTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_get_routes_render(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@joshnu.example.org')->first();

        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods()))
            ->filter(fn ($r) => ! str_contains($r->uri(), '{'))
            ->map(fn ($r) => '/'.$r->uri())
            ->unique()
            ->values();

        $failures = [];
        foreach ($routes as $uri) {
            $res = $this->actingAs($admin)->get($uri);
            $code = $res->getStatusCode();
            if ($code >= 500) {
                $failures[] = $uri.' => '.$code.' '.($res->exception ? $res->exception->getMessage() : '');
            }
        }

        $this->assertEmpty($failures, 'Halaman error 500: '.implode(' | ', $failures));
    }
}
