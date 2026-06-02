<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteVisitTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_website_visit_is_tracked(): void
    {
        $this->get('/')
            ->assertOk();

        $this->assertDatabaseCount('site_visits', 1);
        $this->assertDatabaseHas('site_visits', [
            'route_name' => 'home',
            'path' => '/',
        ]);
    }

    public function test_system_pages_are_not_tracked_as_public_website_visits(): void
    {
        $this->get('/login')
            ->assertOk();

        $this->assertDatabaseCount('site_visits', 0);
    }

    public function test_likely_bots_are_not_tracked(): void
    {
        $this->withHeader('User-Agent', 'Googlebot/2.1')
            ->get('/')
            ->assertOk();

        $this->assertDatabaseCount('site_visits', 0);
    }
}
