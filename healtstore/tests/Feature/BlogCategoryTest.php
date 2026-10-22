<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_blog_category_lists_only_published_active_articles(): void
    {
        $category = ArticleCategory::query()->create([
            'name' => 'مراقبت پوست',
            'slug' => 'skin-care',
            'description' => 'مطالب مراقبت پوست.',
            'is_active' => true,
        ]);

        Article::query()->create([
            'title' => 'مقاله منتشرشده',
            'slug' => 'published-article',
            'content' => 'محتوای مقاله منتشرشده.',
            'category_id' => $category->id,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        Article::query()->create([
            'title' => 'مقاله پیش‌نویس',
            'slug' => 'draft-article',
            'content' => 'محتوای پیش‌نویس.',
            'category_id' => $category->id,
            'is_active' => true,
            'published_at' => null,
        ]);

        Article::query()->create([
            'title' => 'مقاله آینده',
            'slug' => 'future-article',
            'content' => 'محتوای مقاله آینده.',
            'category_id' => $category->id,
            'is_active' => true,
            'published_at' => now()->addDay(),
        ]);

        Article::query()->create([
            'title' => 'مقاله غیرفعال',
            'slug' => 'inactive-article',
            'content' => 'محتوای مقاله غیرفعال.',
            'category_id' => $category->id,
            'is_active' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/blog/category/'.$category->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Category')
            ->where('category.slug', $category->slug)
            ->where('pagination.total', 1)
            ->where('articles.0.slug', 'published-article')
            ->where('structuredData.@type', 'CollectionPage')
            ->where('structuredData.mainEntity.numberOfItems', 1)
            ->where('structuredData.mainEntity.itemListElement.0.url', url('/blog/published-article'))
        );
    }

    public function test_inactive_blog_category_is_not_public(): void
    {
        $category = ArticleCategory::query()->create([
            'name' => 'دسته غیرفعال',
            'slug' => 'inactive-category',
            'is_active' => false,
        ]);

        $this->get('/blog/category/'.$category->slug)->assertNotFound();
    }

    public function test_sitemap_contains_active_blog_categories(): void
    {
        $category = ArticleCategory::query()->create([
            'name' => 'دسته سلامت',
            'slug' => 'health-category',
            'is_active' => true,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk()->assertSee(
            route('blog.categories.show', $category->slug),
            false,
        );
    }
}
