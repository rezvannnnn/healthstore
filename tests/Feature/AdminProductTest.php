<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_product_management_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/products')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/Index')
                ->has('products')
                ->has('pagination')
                ->has('filters')
            );
    }

    public function test_non_admin_users_are_blocked_from_product_management_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/products')
            ->assertForbidden();
    }

    public function test_admin_can_open_product_create_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/products/create')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/Create')
                ->has('brands')
                ->has('categories')
                ->where('product', null)
            );
    }

    public function test_admin_rejects_product_with_past_expiry_date_or_invalid_compare_price(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post('/admin/products', [
                'name' => 'Invalid Product',
                'slug' => 'invalid-product',
                'price' => 125000,
                'compare_at_price' => 100000,
                'expiry_date' => now()->subDay()->toDateString(),
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 0,
            ])
            ->assertSessionHasErrors(['expiry_date', 'compare_at_price']);

        $this->assertDatabaseMissing('products', [
            'slug' => 'invalid-product',
        ]);
    }

    public function test_admin_can_create_product_and_retail_price(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Test Vitamin C',
            'slug' => 'test-vitamin-c',
            'sku' => 'TEST-C-001',
            'price' => 125000,
            'compare_at_price' => 150000,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ]);

        $response->assertRedirect('/admin/products');

        $product = Product::query()->where('sku', 'TEST-C-001')->firstOrFail();

        $this->assertSame('Test Vitamin C', $product->name);
        $this->assertSame('test-vitamin-c', $product->slug);
        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'price_type' => 'retail',
            'min_quantity' => 1,
            'price' => 125000,
            'compare_at_price' => 150000,
            'is_active' => 1,
        ]);
    }

    public function test_admin_can_edit_product_and_update_retail_price(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $product = Product::create([
            'name' => 'Original Product',
            'slug' => 'original-product',
            'sku' => 'ORIGINAL-001',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ]);

        ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => 100000,
            'min_quantity' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get("/admin/products/{$product->id}/edit")
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/Edit')
                ->where('product.id', $product->id)
                ->where('product.price', 100000)
            );

        $response = $this->actingAs($admin)->put("/admin/products/{$product->id}", [
            'name' => 'Updated Product',
            'slug' => 'updated-product',
            'sku' => 'UPDATED-001',
            'price' => 135000,
            'compare_at_price' => 160000,
            'is_active' => true,
            'is_featured' => true,
            'sort_order' => 2,
        ]);

        $response->assertRedirect('/admin/products');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'slug' => 'updated-product',
            'sku' => 'UPDATED-001',
            'is_featured' => 1,
            'sort_order' => 2,
        ]);

        $this->assertDatabaseHas('product_prices', [
            'product_id' => $product->id,
            'price_type' => 'retail',
            'min_quantity' => 1,
            'price' => 135000,
            'compare_at_price' => 160000,
        ]);
    }
}
