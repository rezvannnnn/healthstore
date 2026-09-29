<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get('/admin')
            ->assertRedirect('/login');
    }

    public function test_regular_customer_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'role' => User::ROLE_USER,
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_storagekeeper_can_access_inventory_but_not_other_admin_sections(): void
    {
        $storagekeeper = User::factory()->create([
            'is_admin' => false,
            'role' => User::ROLE_STORAGEKEEPER,
        ]);

        $this->actingAs($storagekeeper)
            ->get('/admin/inventory')
            ->assertOk();

        $this->actingAs($storagekeeper)
            ->get('/admin/orders')
            ->assertForbidden();

        $this->actingAs($storagekeeper)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->has('stats')
                ->where('stats.orders_count', 0)
                ->where('stats.customers_count', 0)
            );
    }
}
