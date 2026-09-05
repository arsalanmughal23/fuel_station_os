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

    // Custom state methods for specific fuel types
    public function forDiesel()
    {
        return $this->state([
            'fuel_type_id' => FuelType::factory()->diesel(),
        ]);
    }

    public function forPetrol()
    {
        return $this->state([
            'fuel_type_id' => FuelType::factory()->petrol(),
        ]);
    }

    public function forHighOctane()
    {
        return $this->state([
            'fuel_type_id' => FuelType::factory()->highOctane(),
        ]);
    }

    // Generic custom methods
    public function withName($name)
    {
        return $this->state([
            'name' => $name,
        ]);
    }

    public function withCapacity($capacity)
    {
        return $this->state([
            'capacity_liters' => $capacity,
        ]);
    }

    public function forFuelType(FuelType $fuelType)
    {
        return $this->state([
            'fuel_type_id' => $fuelType->id,
        ]);
    }
}
