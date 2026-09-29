<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    protected function admin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'role' => User::ROLE_ADMIN,
            'admin_active' => true,
            'admin_permissions' => [],
        ]);
    }

    public function test_admin_can_view_user_management(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Users/Index')
                ->has('users')
                ->has('permissionLabels')
                ->has('permissionGroups')
            );
    }

    public function test_admin_can_create_accountant_with_finance_permissions(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'حسابدار',
            'username' => 'accountant',
            'password' => 'AccountingPass123!',
            'title' => 'حسابدار',
            'is_admin' => false,
            'is_active' => true,
            'permissions' => ['orders.view', 'payments.view', 'reports.view'],
        ])->assertRedirect('/admin/users');

        $staff = User::query()
            ->where('admin_username', 'accountant')
            ->firstOrFail();

        $this->assertSame(User::ROLE_STAFF, $staff->role);
        $this->assertTrue(Hash::check('AccountingPass123!', $staff->password));

        $this->actingAs($staff)
            ->get('/admin/payments')
            ->assertOk();

        $this->actingAs($staff)
            ->get('/admin/products')
            ->assertForbidden();
    }

    public function test_admin_can_create_warehouse_staff_with_product_and_inventory_permissions(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'مسئول انبار',
            'username' => 'warehouse',
            'password' => 'WarehousePass123!',
            'title' => 'مسئول انبار',
            'is_admin' => false,
            'is_active' => true,
            'permissions' => ['products.manage', 'inventory.manage'],
        ])->assertRedirect('/admin/users');

        $staff = User::query()
            ->where('admin_username', 'warehouse')
            ->firstOrFail();

        $this->assertContains('products.view', $staff->admin_permissions);
        $this->assertContains('inventory.view', $staff->admin_permissions);

        $this->actingAs($staff)
            ->get('/admin/products')
            ->assertOk();

        $this->actingAs($staff)
            ->get('/admin/inventory')
            ->assertOk();

        $this->actingAs($staff)
            ->get('/admin/payments')
            ->assertForbidden();
    }

    public function test_staff_cannot_open_user_management(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_admin' => false,
            'admin_active' => true,
            'admin_permissions' => ['inventory.view', 'inventory.manage'],
        ]);

        $this->actingAs($staff)
            ->get('/admin/users')
            ->assertForbidden();
    }
}
