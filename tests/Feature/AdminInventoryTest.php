<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInventoryTest extends TestCase
{
    use RefreshDatabase;

    private function warehouse(): Warehouse
    {
        return Warehouse::create(['name' => 'انبار اصلی', 'code' => 'MAIN', 'is_active' => true]);
    }

    private function product(string $name = 'Test Product'): Product
    {
        return Product::create(['name' => $name, 'slug' => strtolower(str_replace(' ', '-', $name)), 'is_active' => true, 'is_featured' => false, 'sort_order' => 0]);
    }

    public function test_admin_can_view_inventory_management_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get('/admin/inventory')->assertSuccessful()->assertInertia(fn ($page) => $page->component('Admin/Inventory/Index')->has('products')->has('pagination')->has('filters')->has('warehouses'));
    }

    public function test_non_admin_cannot_view_inventory_management_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user)->get('/admin/inventory')->assertForbidden();
    }

    public function test_admin_can_create_inventory_record(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = $this->product();
        $warehouse = $this->warehouse();
        $response = $this->actingAs($admin)->post('/admin/inventory', ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 25, 'minimum_quantity' => 5, 'batch_number' => 'BATCH-001']);
        $response->assertRedirect('/admin/inventory');
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 25, 'minimum_quantity' => 5, 'batch_number' => 'BATCH-001']);
    }

    public function test_admin_can_increase_inventory_and_movement_is_recorded(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = $this->product();
        $warehouse = $this->warehouse();
        $inventory = Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 10, 'minimum_quantity' => 2, 'is_active' => true]);
        $response = $this->actingAs($admin)->post("/admin/inventory/{$inventory->id}/adjust", ['quantity_delta' => 7, 'note' => 'رسید خرید']);
        $response->assertRedirect();
        $this->assertDatabaseHas('inventories', ['id' => $inventory->id, 'quantity' => 17]);
        $this->assertDatabaseHas('inventory_movements', ['inventory_id' => $inventory->id, 'user_id' => $admin->id, 'type' => 'increase', 'quantity_delta' => 7, 'quantity_before' => 10, 'quantity_after' => 17]);
    }

    public function test_admin_cannot_reduce_inventory_below_zero(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = $this->product();
        $warehouse = $this->warehouse();
        $inventory = Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 5, 'minimum_quantity' => 1, 'is_active' => true]);
        $response = $this->actingAs($admin)->post("/admin/inventory/{$inventory->id}/adjust", ['quantity_delta' => -6]);
        $response->assertSessionHasErrors('quantity_delta');
        $this->assertDatabaseHas('inventories', ['id' => $inventory->id, 'quantity' => 5]);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_admin_can_view_inventory_movements(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $product = $this->product();
        $warehouse = $this->warehouse();
        $inventory = Inventory::create(['product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 10, 'minimum_quantity' => 2, 'is_active' => true]);
        InventoryMovement::create(['inventory_id' => $inventory->id, 'user_id' => $admin->id, 'type' => 'increase', 'quantity_delta' => 10, 'quantity_before' => 0, 'quantity_after' => 10]);
        $this->actingAs($admin)->get("/admin/inventory/{$inventory->id}/movements")->assertSuccessful()->assertInertia(fn ($page) => $page->component('Admin/Inventory/Movements')->where('inventory.id', $inventory->id)->has('movements.data', 1));
    }
}
