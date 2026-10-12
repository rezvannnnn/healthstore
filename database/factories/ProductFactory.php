<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return ['name' => fake()->words(3, true), 'slug' => fake()->unique()->slug(), 'sku' => fake()->unique()->bothify('TEST-########'), 'is_active' => true, 'is_featured' => false, 'sort_order' => 0];
    }
}
