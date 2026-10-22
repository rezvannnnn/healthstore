<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StorefrontAccountLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_account_link_opens_login(): void
    {
        $this->assertAccountDestination('login');
    }

    public function test_customer_account_link_opens_own_profile(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_USER, 'is_admin' => false]));

        $this->assertAccountDestination('account.profile.index');
    }

    public function test_admin_account_link_opens_admin_panel(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_active' => true]));

        $this->assertAccountDestination('admin.dashboard');
        $this->get('/account/profile')->assertForbidden();
    }

    public function test_staff_account_link_opens_permitted_section(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_admin' => false,
            'admin_active' => true,
            'admin_permissions' => ['products.view'],
        ]));

        $this->assertAccountDestination('admin.products.index');
    }

    private function assertAccountDestination(string $routeName): void
    {
        $this->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('auth.account_url', route($routeName)));

        $this->get(route($routeName))->assertOk();
    }
}
