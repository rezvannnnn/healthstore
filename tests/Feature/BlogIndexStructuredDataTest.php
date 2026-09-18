<?php

namespace Tests\Feature;

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogIndexStructuredDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_exposes_collection_page_and_article_list_structured_data(): void
    {
        $article = Article::query()->create([
            'title' => 'راهنمای مراقبت از پوست',
            'slug' => 'skin-care-guide',
            'excerpt' => 'راهنمای کاربردی مراقبت از پوست.',
            'content' => 'محتوای مقاله.',
            'is_active' => true,
            'is_featured' => false,
            'published_at' => now()->subMinute(),
        ]);

        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Blog/Index')
            ->where('structuredData.@context', 'https://schema.org')
            ->where('structuredData.@type', 'CollectionPage')
            ->where('structuredData.name', 'مجله سلامت | مطالب آموزشی و کاربردی سلامت')
            ->where('structuredData.url', url('/blog'))
            ->where('structuredData.mainEntity.@type', 'ItemList')
            ->where('structuredData.mainEntity.numberOfItems', 1)
            ->where('structuredData.mainEntity.itemListElement.0.name', $article->title)
            ->where('structuredData.mainEntity.itemListElement.0.url', url('/blog/'.$article->slug))
        );
    }

    public function test_empty_blog_index_keeps_structured_data_valid_without_empty_item_list(): void
    {
        $response = $this->get('/blog');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('structuredData.@type', 'CollectionPage')
            ->missing('structuredData.mainEntity')
        );
    }
}
