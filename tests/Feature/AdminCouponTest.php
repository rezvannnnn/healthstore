<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_coupon_list(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Coupon::create(['code' => 'WELCOME10', 'type' => 'percent', 'value' => 10]);

        $this->actingAs($admin)
            ->get('/admin/coupons')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Coupons/Index')
                ->has('coupons.data', 1)
                ->where('coupons.data.0.code', 'WELCOME10')
            );
    }

    public function test_non_admin_cannot_manage_coupons(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/coupons')->assertForbidden();
    }

    public function test_admin_can_create_coupon(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post('/admin/coupons', [
                'code' => ' summer20 ',
                'type' => 'percent',
                'value' => 20,
                'min_order_amount' => 500000,
                'usage_limit' => 100,
                'usage_limit_per_user' => 1,
                'is_active' => true,
            ])
            ->assertRedirect('/admin/coupons');

        $this->assertDatabaseHas('coupons', [
            'code' => 'SUMMER20',
            'type' => 'percent',
            'value' => 20,
            'min_order_amount' => 500000,
            'usage_limit' => 100,
            'usage_limit_per_user' => 1,
        ]);
    }

    public function test_percent_coupon_cannot_exceed_one_hundred(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post('/admin/coupons', [
                'code' => 'BAD1000',
                'type' => 'percent',
                'value' => 101,
                'is_active' => true,
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('coupons', ['code' => 'BAD1000']);
    }

    public function test_admin_can_update_and_delete_coupon(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $coupon = Coupon::create(['code' => 'OLD10', 'type' => 'percent', 'value' => 10]);

        $this->actingAs($admin)
            ->put("/admin/coupons/{$coupon->id}", [
                'code' => 'NEW15',
                'type' => 'percent',
                'value' => 15,
                'is_active' => false,
            ])
            ->assertRedirect('/admin/coupons');

        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'code' => 'NEW15',
            'value' => 15,
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->delete("/admin/coupons/{$coupon->id}")
            ->assertRedirect('/admin/coupons');

        $this->assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }
}
