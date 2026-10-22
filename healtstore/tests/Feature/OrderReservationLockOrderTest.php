<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CartService;
use App\Services\InventoryReservationService;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReservationLockOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_product_reservations_follow_product_id_order(): void
    {
        $user = User::factory()->create();
        $productA = $this->createProduct('Product A', 'product-a', 'LOCK-A');
        $productB = $this->createProduct('Product B', 'product-b', 'LOCK-B');

        $this->createPrice($productA, 100000);
        $this->createPrice($productB, 120000);
        $this->createInventory($productA, 5);
        $this->createInventory($productB, 5);

        $cartService = new CartService;
        $cartService->addItem($user->id, $productB->id, 1);
        $cartService->addItem($user->id, $productA->id, 1);

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $order = (new OrderService(
            new InventoryService,
            $cartService,
            new InventoryReservationService
        ))->createFromCart($cart);

        $this->assertSame(
            [$productA->id, $productB->id],
            $order->inventoryReservations()->orderBy('id')->pluck('product_id')->all()
        );
    }

    private function createProduct(string $name, string $slug, string $sku): Product
    {
        return Product::create([
            'brand_id' => null,
            'category_id' => null,
            'name' => $name,
            'slug' => $slug,
            'sku' => $sku,
            'product_type' => 'physical',
            'unit' => 'piece',
            'quantity_per_unit' => 1,
            'short_description' => null,
            'description' => null,
            'specifications' => null,
            'expiry_date' => null,
            'main_image' => null,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 1,
        ]);
    }

    private function createPrice(Product $product, int $price): void
    {
        ProductPrice::create([
            'product_id' => $product->id,
            'price_type' => 'retail',
            'price' => $price,
            'compare_at_price' => null,
            'min_quantity' => 1,
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
        ]);
    }

    private function createInventory(Product $product, int $quantity): void
    {
        $warehouse = Warehouse::create([
            'name' => 'Lock Order Test Warehouse',
            'code' => 'LOCK-ORDER-WH-'.$product->id,
            'description' => null,
            'is_active' => true,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => $quantity,
            'minimum_quantity' => 1,
            'batch_number' => 'LOCK-ORDER-BATCH-'.$product->id,
            'expiry_date' => null,
            'is_active' => true,
        ]);
    }
}
