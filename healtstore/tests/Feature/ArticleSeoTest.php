<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_article_exposes_an_absolute_featured_image_url(): void
    {
        $article = Article::query()->create([
            'title' => 'راهنمای مراقبت از پوست',
            'slug' => 'skin-care-guide',
            'content' => 'محتوای مقاله.',
            'featured_image' => '/storage/articles/skin-care.jpg',
            'featured_image_alt' => 'مراقبت از پوست',
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/blog/'.$article->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Show')
            ->where('article.featured_image', url('/storage/articles/skin-care.jpg'))
            ->where('article.featured_image_alt', 'مراقبت از پوست')
        );
    }

    public function test_public_article_preserves_an_existing_absolute_featured_image_url(): void
    {
        $article = Article::query()->create([
            'title' => 'تغذیه سالم',
            'slug' => 'healthy-nutrition',
            'content' => 'محتوای مقاله.',
            'featured_image' => 'https://cdn.example.com/articles/healthy.jpg',
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/blog/'.$article->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('article.featured_image', 'https://cdn.example.com/articles/healthy.jpg')
        );
    }
}
