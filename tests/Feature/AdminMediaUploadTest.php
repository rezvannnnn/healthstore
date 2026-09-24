<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMediaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_upload_product_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())->post('/admin/products', [
            'name' => 'محصول تستی',
            'price' => 100000,
            'main_image_file' => UploadedFile::fake()->image('product.jpg'),
        ]);

        $response->assertRedirect('/admin/products');

        $product = Product::query()->firstOrFail();

        $this->assertNotNull($product->main_image);
        Storage::disk('public')->assertExists($product->main_image);
    }

    public function test_admin_can_upload_brand_logo(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())->post('/admin/brands', [
            'name' => 'برند تستی',
            'logo_file' => UploadedFile::fake()->image('brand.png'),
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/brands');

        $brand = Brand::query()->firstOrFail();

        $this->assertNotNull($brand->logo);
        Storage::disk('public')->assertExists($brand->logo);
    }

    public function test_admin_can_upload_category_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())->post('/admin/categories', [
            'name' => 'دسته تستی',
            'image_file' => UploadedFile::fake()->image('category.webp'),
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response->assertRedirect('/admin/categories');

        $category = Category::query()->firstOrFail();

        $this->assertNotNull($category->image);
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_admin_can_upload_article_featured_image(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())->post('/admin/articles', [
            'title' => 'مقاله تستی',
            'content' => 'محتوای تستی',
            'featured_image_file' => UploadedFile::fake()->image('article.jpg'),
            'is_active' => false,
            'is_featured' => false,
        ]);

        $response->assertRedirect('/admin/articles');

        $article = Article::query()->firstOrFail();

        $this->assertNotNull($article->featured_image);
        Storage::disk('public')->assertExists($article->featured_image);
    }
}
