<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_articles(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/admin/articles');

        $response->assertOk();
    }

    public function test_non_admin_cannot_view_articles(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get('/admin/articles');

        $response->assertForbidden();
    }

    public function test_admin_can_create_and_publish_article(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = ArticleCategory::create([
            'name' => 'سلامت',
            'slug' => 'health',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/articles', [
            'category_id' => $category->id,
            'title' => 'راهنمای سلامت',
            'slug' => 'health-guide',
            'excerpt' => 'مقدمه کوتاه',
            'content' => 'متن کامل مقاله',
            'featured_image' => '/images/health.jpg',
            'featured_image_alt' => 'راهنمای سلامت',
            'seo_title' => 'راهنمای سلامت | داروخونه',
            'seo_description' => 'توضیحات سئو',
            'canonical_url' => 'https://example.com/blog/health-guide',
            'is_active' => true,
            'is_featured' => true,
            'published_at' => now()->toDateTimeString(),
        ]);

        $response->assertRedirect('/admin/articles');
        $this->assertDatabaseHas('articles', [
            'title' => 'راهنمای سلامت',
            'slug' => 'health-guide',
            'author_id' => $admin->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_public_only_shows_published_articles(): void
    {
        $published = Article::create([
            'title' => 'مقاله منتشرشده',
            'slug' => 'published-article',
            'content' => 'متن منتشرشده',
            'is_active' => true,
            'published_at' => now()->subHour(),
        ]);

        Article::create([
            'title' => 'پیش‌نویس',
            'slug' => 'draft-article',
            'content' => 'متن پیش‌نویس',
            'is_active' => false,
            'published_at' => null,
        ]);

        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Index')
            ->where('articles.0.slug', $published->slug)
            ->missing('articles.1'));
    }

    public function test_public_article_page_requires_published_article(): void
    {
        Article::create([
            'title' => 'پیش‌نویس',
            'slug' => 'hidden-draft',
            'content' => 'متن',
            'is_active' => false,
        ]);

        $this->get('/blog/hidden-draft')->assertNotFound();
    }
}
