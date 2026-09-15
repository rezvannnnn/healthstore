<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogShowSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_article_uses_fallback_canonical_and_description(): void
    {
        $article = Article::query()->create([
            'title' => 'راهنمای سلامت',
            'slug' => 'health-guide-seo',
            'excerpt' => 'یک توضیح کوتاه برای مقاله سلامت.',
            'content' => 'محتوای مقاله.',
            'is_active' => true,
            'is_featured' => false,
            'published_at' => now()->subMinute(),
        ]);

        $response = $this->get('/blog/'.$article->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Show')
            ->where('article.canonical_url', url('/blog/'.$article->slug))
            ->where('article.seo_description', $article->excerpt)
        );
    }
}
