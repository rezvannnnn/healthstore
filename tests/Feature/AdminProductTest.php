<?php

namespace Tests\Feature;

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
}
