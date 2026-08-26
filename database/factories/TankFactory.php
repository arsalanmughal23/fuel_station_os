<?php

namespace Database\Factories;

use App\Models\Tank;
use App\Models\FuelType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tank>
 */
class TankFactory extends Factory
{
    protected $model = Tank::class;

    public function definition(): array
    {
        return [
            'fuel_type_id' => FuelType::factory(),
            'name' => fake()->word() . ' Tank',
            'capacity_liters' => fake()->randomFloat(2, 5000, 50000),
            'calculated_stock' => 0,
        ];
    }
}