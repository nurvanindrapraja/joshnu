<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_announcements_link_is_translated(): void
    {
        $this->seed();

        // bahasa Indonesia
        $this->get('/locale/id')->assertRedirect();
        $this->get('/')->assertOk()->assertSee('Semua pengumuman');

        // bahasa Inggris
        $this->get('/locale/en')->assertRedirect();
        $this->get('/')->assertOk()->assertSee('All Announcements');
    }
}
