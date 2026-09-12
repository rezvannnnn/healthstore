<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_category_management_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Category::create(['name' => 'ویتامین‌ها', 'slug' => 'vitamins', 'is_active' => true]);

        $this->actingAs($admin)
            ->get('/admin/categories')
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Categories/Index')
                ->has('categories', 1)
            );
    }

    public function test_admin_can_create_and_edit_category(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post('/admin/categories', [
                'name' => 'مراقبت پوست',
                'slug' => 'skin-care',
                'description' => 'محصولات مراقبت از پوست',
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->assertRedirect('/admin/categories');

        $category = Category::query()->where('slug', 'skin-care')->firstOrFail();

        $this->actingAs($admin)
            ->put('/admin/categories/'.$category->id, [
                'name' => 'مراقبت تخصصی پوست',
                'slug' => 'advanced-skin-care',
                'description' => 'توضیحات جدید',
                'is_active' => true,
                'sort_order' => 2,
            ])
            ->assertRedirect('/admin/categories');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'مراقبت تخصصی پوست',
            'slug' => 'advanced-skin-care',
            'sort_order' => 2,
        ]);
    }

    public function test_admin_can_create_and_edit_brand(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post('/admin/brands', [
                'name' => 'Test Brand',
                'slug' => 'test-brand',
                'description' => 'Brand description',
                'is_active' => true,
            ])
            ->assertRedirect('/admin/brands');

        $brand = Brand::query()->where('slug', 'test-brand')->firstOrFail();

        $this->actingAs($admin)
            ->put('/admin/brands/'.$brand->id, [
                'name' => 'Updated Brand',
                'slug' => 'updated-brand',
                'description' => 'Updated description',
                'is_active' => true,
            ])
            ->assertRedirect('/admin/brands');

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Updated Brand',
            'slug' => 'updated-brand',
        ]);
    }

    public function test_non_admin_users_are_blocked_from_catalog_management(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/admin/categories')->assertForbidden();
        $this->actingAs($user)->get('/admin/brands')->assertForbidden();
    }
}
