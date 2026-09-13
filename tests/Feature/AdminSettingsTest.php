<?php

namespace Tests\Feature;

use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_store_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.settings.index'))
            ->assertSuccessful();
    }

    public function test_non_admin_cannot_view_store_settings(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.settings.index'))
            ->assertForbidden();
    }

    public function test_admin_can_update_store_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'store_name' => 'داروخونه آنلاین',
            'support_phone' => '02112345678',
            'support_email' => 'support@example.com',
            'store_address' => 'تهران',
            'shipping_fee' => 50000,
            'free_shipping_threshold' => 1000000,
            'min_order_amount' => 100000,
            'currency' => 'تومان',
            'timezone' => 'Asia/Tehran',
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $this->assertSame('داروخونه آنلاین', StoreSetting::getValue('store_name'));
        $this->assertSame('50000', StoreSetting::getValue('shipping_fee'));
    }
}
