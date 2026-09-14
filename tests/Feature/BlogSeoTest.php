<?php

namespace Tests\Feature;

use Tests\TestCase;

class BlogSeoTest extends TestCase
{
    public function test_blog_index_is_public_and_renders_successfully(): void
    {
        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertSee('مجله سلامت');
    }
}
