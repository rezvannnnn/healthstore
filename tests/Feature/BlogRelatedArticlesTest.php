<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogRelatedArticlesTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_article_exposes_related_published_articles_from_same_category(): void
    {
        $category = ArticleCategory::query()->create([
            'name' => 'مراقبت پوست',
            'slug' => 'skin-care',
            'is_active' => true,
        ]);

        $article = Article::query()->create([
            'title' => 'مقاله اصلی',
            'slug' => 'main-article',
            'content' => 'محتوای مقاله اصلی.',
            'category_id' => $category->id,
            'is_active' => true,
            'published_at' => now()->subDays(3),
        ]);

        Article::query()->create([
            'title' => 'مقاله مرتبط',
            'slug' => 'related-article',
            'content' => 'محتوای مقاله مرتبط.',
            'category_id' => $category->id,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        Article::query()->create([
            'title' => 'مقاله آینده',
            'slug' => 'future-related',
            'content' => 'محتوای مقاله آینده.',
            'category_id' => $category->id,
            'is_active' => true,
            'published_at' => now()->addDay(),
        ]);

        $otherCategory = ArticleCategory::query()->create([
            'name' => 'مو و زیبایی',
            'slug' => 'hair-beauty',
            'is_active' => true,
        ]);

        Article::query()->create([
            'title' => 'مقاله دسته دیگر',
            'slug' => 'other-category',
            'content' => 'محتوای دسته دیگر.',
            'category_id' => $otherCategory->id,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/blog/'.$article->slug);

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Show')
            ->where('relatedArticles', fn ($articles) => count($articles) === 1)
            ->where('relatedArticles.0.slug', 'related-article')
            ->where('relatedArticles.0.title', 'مقاله مرتبط')
        );
    }
}
