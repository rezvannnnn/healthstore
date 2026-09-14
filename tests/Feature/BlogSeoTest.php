<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_has_descriptive_title(): void
    {
        Article::factory()->create([
            'title' => 'راهنمای سلامت',
            'slug' => 'health-guide',
            'is_published' => true,
        ]);

        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertSee('مجله سلامت');
    }
}
