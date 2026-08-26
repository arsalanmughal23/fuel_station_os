<?php

namespace Database\Factories;

use App\Models\Product;
use App\Enums\ProductCategory;
use App\Enums\ScaleUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'title' => fake()->word(),
            'slug' => fake()->unique()->slug(),
            'category' => fake()->randomElement(array_column(ProductCategory::cases(), 'value')),
            'unit' => fake()->randomElement(array_column(ScaleUnit::cases(), 'value')),
            'unit_price' => fake()->randomFloat(2, 10, 1000),
            'current_stock' => fake()->randomFloat(2, 0, 1000),
        ];
    }
}