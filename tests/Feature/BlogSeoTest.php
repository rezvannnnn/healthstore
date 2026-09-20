<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_is_public_and_renders_successfully(): void
    {
        $response = $this->get('/blog');

        $response->assertOk();
    }

    public function test_published_article_exposes_seo_contract(): void
    {
        $article = Article::create([
            'title' => 'راهنمای مصرف صحیح محصولات بهداشتی',
            'slug' => 'hygiene-products-guide',
            'excerpt' => 'راهنمای کوتاه برای انتخاب و مصرف صحیح محصولات بهداشتی.',
            'content' => 'متن کامل مقاله',
            'featured_image' => '/images/hygiene-guide.jpg',
            'featured_image_alt' => 'راهنمای محصولات بهداشتی',
            'seo_title' => 'راهنمای محصولات بهداشتی | داروخونه',
            'seo_description' => 'نکات کاربردی برای انتخاب و مصرف صحیح محصولات بهداشتی.',
            'canonical_url' => 'https://example.com/blog/hygiene-products-guide',
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        $response = $this->get('/blog/'.$article->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Show')
            ->where('article.slug', $article->slug)
            ->where('article.seo_title', $article->seo_title)
            ->where('article.seo_description', $article->seo_description)
            ->where('article.canonical_url', $article->canonical_url)
            ->where('article.featured_image', url($article->featured_image)));
    }

    public function test_blog_search_rejects_overlong_input(): void
    {
        $this->get('/blog?search='.str_repeat('a', 201))
            ->assertSessionHasErrors('search');
    }

    public function test_blog_search_rejects_non_string_input(): void
    {
        $this->get('/blog?search[]=health')
            ->assertSessionHasErrors('search');
    }

    public function test_article_seo_falls_back_to_excerpt_and_slug_canonical(): void
    {
        $article = Article::create([
            'title' => 'مقاله سلامت',
            'slug' => 'health-article',
            'excerpt' => 'توضیحات خلاصه مقاله',
            'content' => 'متن مقاله',
            'is_active' => true,
            'published_at' => now()->subMinute(),
        ]);

        $response = $this->get('/blog/'.$article->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Show')
            ->where('article.seo_description', $article->excerpt)
            ->where('article.canonical_url', url('/blog/'.$article->slug)));
    }

    public function test_unpublished_or_inactive_article_is_not_public(): void
    {
        Article::create([
            'title' => 'مقاله زمان‌دار',
            'slug' => 'scheduled-health-article',
            'content' => 'متن',
            'is_active' => true,
            'published_at' => now()->addHour(),
        ]);

        Article::create([
            'title' => 'مقاله غیرفعال',
            'slug' => 'inactive-health-article',
            'content' => 'متن',
            'is_active' => false,
            'published_at' => now()->subHour(),
        ]);

        $this->get('/blog/scheduled-health-article')->assertNotFound();
        $this->get('/blog/inactive-health-article')->assertNotFound();
    }
}
