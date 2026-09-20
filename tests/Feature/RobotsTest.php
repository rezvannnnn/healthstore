<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RobotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_is_public_and_points_to_absolute_sitemap_url(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk()
            ->assertHeader('content-type', 'text/plain; charset=UTF-8')
            ->assertSee('User-agent: *')
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /account')
            ->assertSee('Disallow: /checkout')
            ->assertSee('Disallow: /orders')
            ->assertSee('Disallow: /cart')
            ->assertSee('Sitemap: '.route('sitemap'), false);
    }
}
