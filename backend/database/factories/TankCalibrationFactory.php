<?php

namespace Database\Factories;

use App\Models\TankCalibration;
use App\Models\Tank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TankCalibration>
 */
class TankCalibrationFactory extends Factory
{
    protected $model = TankCalibration::class;

    public function definition(): array
    {
        return [
            'tank_id' => Tank::factory(),
            'deep_cm' => fake()->randomFloat(2, 0, 300),
            'volume_liters' => fake()->randomFloat(2, 0, 50000),
        ];
    }
}