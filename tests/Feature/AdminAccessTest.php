<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_regular_customer_cannot_access_admin_area(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
            'role' => User::ROLE_USER,
            'admin_active' => true,
            'admin_permissions' => [],
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'role' => User::ROLE_ADMIN,
            'admin_active' => true,
            'admin_permissions' => [],
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Dashboard')
                ->has('stats')
            );
    }

    public function test_staff_can_access_only_granted_sections(): void
    {
        $staff = User::factory()->create([
            'is_admin' => false,
            'role' => User::ROLE_STAFF,
            'admin_active' => true,
            'admin_permissions' => ['inventory.view', 'inventory.manage'],
        ]);

        $this->actingAs($staff)
            ->get('/admin/inventory')
            ->assertOk();

        $this->actingAs($staff)
            ->get('/admin/orders')
            ->assertForbidden();
    }

    public function test_staff_dashboard_redirects_to_first_allowed_section(): void
    {
        $staff = User::factory()->create([
            'is_admin' => false,
            'role' => User::ROLE_STAFF,
            'admin_active' => true,
            'admin_permissions' => ['inventory.view'],
        ]);

        $this->actingAs($staff)
            ->get('/admin')
            ->assertRedirect('/admin/inventory');
    }

    public function test_inactive_admin_cannot_access_admin_area(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'role' => User::ROLE_ADMIN,
            'admin_active' => false,
            'admin_permissions' => [],
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_login_route_is_public(): void
    {
        $this->get('/admin/login')
            ->assertOk();
    }

    public function test_admin_login_uses_username_and_password(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'role' => User::ROLE_ADMIN,
            'admin_active' => true,
            'admin_username' => 'admin',
            'password' => Hash::make('AdminPass123!'),
            'admin_permissions' => [],
        ]);

        $this->post('/admin/login', [
            'username' => 'admin',
            'password' => 'AdminPass123!',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
    }
}
