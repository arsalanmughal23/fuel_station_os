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

    // Generic custom methods
    public function withName($name)
    {
        return $this->state([
            'name' => $name,
        ]);
    }

    public function forTank(Tank $tank)
    {
        return $this->state([
            'tank_id' => $tank->id,
        ]);
    }
}
