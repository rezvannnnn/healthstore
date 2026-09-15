<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminArticleSeoContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_persist_custom_article_seo_metadata(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/articles', [
            'title' => 'راهنمای مصرف صحیح',
            'content' => 'متن مقاله',
            'seo_title' => 'راهنمای مصرف صحیح | داروخونه',
            'seo_description' => 'راهنمای کاربردی برای مصرف صحیح محصولات سلامت.',
            'canonical_url' => 'https://example.com/blog/correct-usage',
            'is_active' => false,
        ]);

        $response->assertRedirect('/admin/articles');
        $this->assertDatabaseHas('articles', [
            'title' => 'راهنمای مصرف صحیح',
            'seo_title' => 'راهنمای مصرف صحیح | داروخونه',
            'seo_description' => 'راهنمای کاربردی برای مصرف صحیح محصولات سلامت.',
            'canonical_url' => 'https://example.com/blog/correct-usage',
        ]);
    }

    public function test_article_seo_description_accepts_maximum_allowed_length(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $description = str_repeat('س', 320);

        $this->actingAs($admin)
            ->post('/admin/articles', [
                'title' => 'مقاله طولانی توضیحات سئو',
                'content' => 'متن مقاله',
                'seo_description' => $description,
                'is_active' => false,
            ])
            ->assertRedirect('/admin/articles');

        $this->assertDatabaseHas('articles', ['seo_description' => $description]);
    }

    public function test_article_seo_description_rejects_oversized_value(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post('/admin/articles', [
                'title' => 'مقاله نامعتبر',
                'content' => 'متن مقاله',
                'seo_description' => str_repeat('س', 321),
                'is_active' => false,
            ])
            ->assertSessionHasErrors('seo_description');
    }

    public function test_article_canonical_url_must_be_valid(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post('/admin/articles', [
                'title' => 'مقاله با canonical نامعتبر',
                'content' => 'متن مقاله',
                'canonical_url' => 'not-a-url',
                'is_active' => false,
            ])
            ->assertSessionHasErrors('canonical_url');
    }

    public function test_article_edit_page_exposes_seo_fields(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $article = Article::query()->create([
            'title' => 'مقاله موجود',
            'slug' => 'existing-seo-article',
            'content' => 'متن',
            'seo_title' => 'عنوان سئو',
            'seo_description' => 'توضیح سئو',
            'canonical_url' => 'https://example.com/blog/existing-seo-article',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)->get('/admin/articles/'.$article->id.'/edit');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Articles/Edit')
            ->where('article.seo_title', 'عنوان سئو')
            ->where('article.seo_description', 'توضیح سئو')
            ->where('article.canonical_url', 'https://example.com/blog/existing-seo-article')
        );
    }
}
