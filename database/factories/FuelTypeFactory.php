<?php

namespace Database\Factories;

use App\Models\FuelType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FuelType>
 */
class FuelTypeFactory extends Factory
{
    protected $model = FuelType::class;

    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['Petrol', 'Diesel', 'CNG', 'LPG', 'Kerosene']),
            'slug' => fake()->unique()->slug(),
            'current_price' => fake()->randomFloat(2, 50, 200),
        ];
    }
}