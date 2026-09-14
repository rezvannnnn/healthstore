<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_is_public_and_renders_successfully(): void
    {
        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertSee('مجله سلامت');
    }
}
