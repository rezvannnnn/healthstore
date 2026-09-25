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

    public function test_admin_can_replace_stored_product_image(): void
    {
        Storage::fake('public');

        $oldImage = 'products/old.jpg';
        Storage::disk('public')->put($oldImage, 'old');

        $product = Product::create([
            'name' => 'محصول موجود',
            'slug' => 'existing-product',
            'main_image' => $oldImage,
        ]);

        $response = $this->actingAs($this->admin())->put("/admin/products/{$product->id}", [
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => 100000,
            'main_image' => $oldImage,
            'main_image_file' => UploadedFile::fake()->image('replacement.jpg'),
        ]);

        $response->assertRedirect('/admin/products');
        $product->refresh();

        $this->assertNotSame($oldImage, $product->main_image);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($product->main_image);
    }

    public function test_admin_can_replace_stored_brand_logo(): void
    {
        Storage::fake('public');

        $oldLogo = 'brands/old.png';
        Storage::disk('public')->put($oldLogo, 'old');

        $brand = Brand::create([
            'name' => 'برند موجود',
            'slug' => 'existing-brand',
            'logo' => $oldLogo,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin())->put("/admin/brands/{$brand->id}", [
            'name' => $brand->name,
            'slug' => $brand->slug,
            'logo' => $oldLogo,
            'logo_file' => UploadedFile::fake()->image('replacement.png'),
            'is_active' => true,
        ]);

        $response->assertRedirect('/admin/brands');
        $brand->refresh();

        $this->assertNotSame($oldLogo, $brand->logo);
        Storage::disk('public')->assertMissing($oldLogo);
        Storage::disk('public')->assertExists($brand->logo);
    }

    public function test_admin_can_replace_stored_category_image(): void
    {
        Storage::fake('public');

        $oldImage = 'categories/old.webp';
        Storage::disk('public')->put($oldImage, 'old');

        $category = Category::create([
            'name' => 'دسته موجود',
            'slug' => 'existing-category',
            'image' => $oldImage,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($this->admin())->put("/admin/categories/{$category->id}", [
            'name' => $category->name,
            'slug' => $category->slug,
            'image' => $oldImage,
            'image_file' => UploadedFile::fake()->image('replacement.webp'),
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $response->assertRedirect('/admin/categories');
        $category->refresh();

        $this->assertNotSame($oldImage, $category->image);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($category->image);
    }

    public function test_admin_can_replace_and_delete_article_featured_image(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $oldImage = 'articles/old.jpg';
        Storage::disk('public')->put($oldImage, 'old');

        $article = Article::create([
            'title' => 'مقاله موجود',
            'slug' => 'existing-article',
            'content' => 'محتوای موجود',
            'featured_image' => $oldImage,
            'is_active' => false,
            'is_featured' => false,
        ]);

        $response = $this->actingAs($admin)->put("/admin/articles/{$article->id}", [
            'title' => $article->title,
            'slug' => $article->slug,
            'content' => $article->content,
            'featured_image' => $oldImage,
            'featured_image_file' => UploadedFile::fake()->image('replacement.jpg'),
            'is_active' => false,
            'is_featured' => false,
        ]);

        $response->assertRedirect('/admin/articles');
        $article->refresh();

        $this->assertNotSame($oldImage, $article->featured_image);
        Storage::disk('public')->assertMissing($oldImage);
        Storage::disk('public')->assertExists($article->featured_image);

        $newImage = $article->featured_image;
        $response = $this->actingAs($admin)->delete("/admin/articles/{$article->id}");

        $response->assertRedirect('/admin/articles');
        Storage::disk('public')->assertMissing($newImage);
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin())->post('/admin/products', [
            'name' => 'محصول نامعتبر',
            'price' => 100000,
            'main_image_file' => UploadedFile::fake()->create('script.php', 10, 'text/plain'),
        ]);

        $response->assertSessionHasErrors('main_image_file');
        $this->assertDatabaseMissing('products', ['name' => 'محصول نامعتبر']);
        Storage::disk('public')->assertDirectoryEmpty('products');
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
