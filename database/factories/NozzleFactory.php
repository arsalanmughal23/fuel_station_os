<?php

namespace Database\Factories;

use App\Models\Nozzle;
use App\Models\Tank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Nozzle>
 */
class NozzleFactory extends Factory
{
    protected $model = Nozzle::class;

    public function definition(): array
    {
        return [
            'tank_id' => Tank::factory(),
            'name' => fake()->word() . ' Nozzle',
        ];
    }
}