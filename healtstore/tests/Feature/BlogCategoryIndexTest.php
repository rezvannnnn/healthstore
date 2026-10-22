<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogCategoryIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_blog_category_index_lists_active_categories_with_published_counts(): void
    {
        $category = ArticleCategory::query()->create([
            'name' => 'مراقبت پوست',
            'slug' => 'skin-care',
            'description' => 'مطالب مراقبت از پوست.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Article::query()->create([
            'title' => 'مقاله منتشرشده',
            'slug' => 'published-article',
            'content' => 'محتوای مقاله.',
            'category_id' => $category->id,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        Article::query()->create([
            'title' => 'مقاله آینده',
            'slug' => 'future-article',
            'content' => 'محتوای مقاله آینده.',
            'category_id' => $category->id,
            'is_active' => true,
            'published_at' => now()->addDay(),
        ]);

        ArticleCategory::query()->create([
            'name' => 'دسته غیرفعال',
            'slug' => 'inactive-category',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $response = $this->get('/blog/categories');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Categories')
            ->where('seo.title', 'دسته‌بندی‌های مجله | مجله سلامت')
            ->where('seo.canonical', url('/blog/categories'))
            ->where('pagination.total', 1)
            ->where('categories.0.name', $category->name)
            ->where('categories.0.articles_count', 1)
            ->where('structuredData.@type', 'CollectionPage')
            ->where('structuredData.url', url('/blog/categories'))
            ->where('structuredData.mainEntity.@type', 'ItemList')
            ->where('structuredData.mainEntity.numberOfItems', 1)
            ->where('structuredData.mainEntity.itemListElement.0.url', url('/blog/category/'.$category->slug))
        );
    }

    public function test_blog_index_exposes_active_category_links(): void
    {
        $category = ArticleCategory::query()->create([
            'name' => 'تغذیه',
            'slug' => 'nutrition',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Index')
            ->where('categories.0.slug', $category->slug)
            ->where('categories.0.name', $category->name)
        );
    }

    public function test_sitemap_contains_blog_category_index(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertSee(route('blog.categories.index'), false);
    }
}
