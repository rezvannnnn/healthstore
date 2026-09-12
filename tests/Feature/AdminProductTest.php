<?php

use App\Models\User;

it('allows admins to view the product management page', function () {
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
});

it('blocks non-admin users from the product management page', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)
        ->get('/admin/products')
        ->assertForbidden();
});
