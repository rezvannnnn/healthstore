<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    private function pricedProduct(int $amount): Product
    {
        $product = Product::factory()->create();
        ProductPrice::create(['product_id' => $product->id, 'price_type' => 'retail', 'price' => $amount, 'min_quantity' => 1, 'is_active' => true]);

        return $product;
    }

    private function stock(Product $product, ?string $expiry, int $quantity = 5): Inventory
    {
        $warehouse = Warehouse::firstOrCreate(['code' => 'DISCOVERY'], ['name' => 'Discovery warehouse', 'is_active' => true]);

        return Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => $quantity, 'expiry_date' => $expiry, 'is_active' => true]);
    }

    public function test_price_order_uses_current_single_item_price_and_puts_unpriced_products_last(): void
    {
        $cheap = $this->pricedProduct(100000);
        $expensive = $this->pricedProduct(300000);
        $unpriced = Product::factory()->create();
        ProductPrice::create(['product_id' => $expensive->id, 'price_type' => 'retail', 'price' => 1, 'min_quantity' => 1, 'is_active' => true, 'ends_at' => now()->subDay()]);
        ProductPrice::create(['product_id' => $expensive->id, 'price_type' => 'retail', 'price' => 2, 'min_quantity' => 10, 'is_active' => true]);

        $this->get('/products?sort=price_asc')->assertInertia(fn ($page) => $page
            ->where('products.0.id', $cheap->id)->where('products.1.price', 300000)->where('products.2.id', $unpriced->id)->where('seo.robots', 'noindex,follow'));
        $this->get('/products?sort=price_desc')->assertInertia(fn ($page) => $page
            ->where('products.0.id', $expensive->id)->where('products.1.id', $cheap->id)->where('products.2.id', $unpriced->id));
    }

    public function test_price_limits_are_inclusive_and_accept_a_single_limit(): void
    {
        $cheap = $this->pricedProduct(100000);
        $this->pricedProduct(300000);
        Product::factory()->create();
        foreach (['min_price=100000&max_price=100000', 'max_price=100000'] as $query) {
            $this->get('/products?'.$query)->assertInertia(fn ($page) => $page->has('products', 1)->where('products.0.id', $cheap->id));
        }
        $this->get('/products?min_price=300000')->assertInertia(fn ($page) => $page->has('products', 1)->where('products.0.price', 300000));
        $this->get('/products?min_price=300000&max_price=100000')->assertSessionHasErrors('max_price');
        $this->get('/products?sort=invalid')->assertSessionHasErrors('sort');
    }

    public function test_stock_filter_excludes_expired_inactive_and_fully_reserved_stock_before_pagination(): void
    {
        $available = $this->pricedProduct(100000);
        $this->stock($available, today()->toDateString());
        $expired = $this->pricedProduct(200000);
        $this->stock($expired, today()->subDay()->toDateString());
        $reserved = $this->pricedProduct(300000);
        $inventory = $this->stock($reserved, null);
        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'DISCOVERY-1', 'user_id' => $user->id, 'status' => 'pending', 'payment_status' => 'pending', 'customer_type' => 'b2c', 'subtotal' => 300000, 'total_amount' => 300000, 'currency' => 'IRR']);
        InventoryReservation::create(['order_id' => $order->id, 'product_id' => $reserved->id, 'inventory_id' => $inventory->id, 'quantity' => 5, 'status' => 'active', 'expires_at' => now()->addMinutes(20)]);
        Product::factory()->count(15)->create();

        $this->get('/products?in_stock=1')->assertInertia(fn ($page) => $page->has('products', 1)->where('products.0.id', $available->id)->where('pagination.total', 1));
        InventoryReservation::query()->update(['expires_at' => now()->subMinute()]);
        $this->get('/products?in_stock=1')->assertInertia(fn ($page) => $page->has('products', 2)->where('pagination.total', 2));
    }

    public function test_expiry_comes_from_live_stock_and_discloses_unknown_batches(): void
    {
        $product = $this->pricedProduct(100000);
        $early = today()->addMonths(2)->toDateString();
        $late = today()->addMonths(6)->toDateString();
        $earlyBatch = $this->stock($product, $early);
        $this->stock($product, $late);
        $this->stock($product, null);
        $this->stock($product, today()->subDay()->toDateString());
        $this->stock($product, today()->addDay()->toDateString(), 0);
        $this->get('/products/'.$product->slug)->assertInertia(fn ($page) => $page
            ->where('product.expiry.earliest', $early)->where('product.expiry.latest', $late)->where('product.expiry.incomplete', true)->where('product.available_quantity', 15));

        $user = User::factory()->create();
        $order = Order::create(['order_number' => 'EXPIRY-1', 'user_id' => $user->id, 'status' => 'pending', 'payment_status' => 'pending', 'customer_type' => 'b2c', 'subtotal' => 100000, 'total_amount' => 100000, 'currency' => 'IRR']);
        InventoryReservation::create(['order_id' => $order->id, 'product_id' => $product->id, 'inventory_id' => $earlyBatch->id, 'quantity' => 5, 'status' => 'active', 'expires_at' => now()->addMinutes(20)]);
        $this->get('/products/'.$product->slug)->assertInertia(fn ($page) => $page->where('product.expiry.earliest', $late)->where('product.available_quantity', 10));
    }

    public function test_homepage_shows_real_products_even_without_featured_flags(): void
    {
        $product = $this->pricedProduct(100000);
        $this->get('/')->assertInertia(fn ($page) => $page->has('featuredProducts', 1)->where('featuredProducts.0.id', $product->id)->where('featuredProducts.0.available', false));
    }

    public function test_public_faq_can_be_managed_and_private_settings_are_not_shared(): void
    {
        StoreSetting::setValue('private_key', 'DO-NOT-SHARE');
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->put('/admin/settings', [
            'store_name' => 'داروخونه', 'shipping_fee' => 0, 'free_shipping_threshold' => 0, 'min_order_amount' => 0,
            'currency' => 'ریال', 'timezone' => 'Asia/Tehran', 'license_url' => 'https://example.com/verify',
            'faqs' => [['question' => 'پرسش آزمایشی', 'answer' => 'پاسخ آزمایشی']],
        ])->assertSessionHasNoErrors();
        $this->get('/information/faq')->assertInertia(fn ($page) => $page->where('faqs.0.question', 'پرسش آزمایشی')->missing('storefront.private_key'));
        $this->actingAs($admin)->put('/admin/settings', ['license_url' => 'javascript:alert(1)'])->assertSessionHasErrors('license_url');
    }
}
