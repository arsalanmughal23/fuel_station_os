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

    // Custom state methods
    public function diesel()
    {
        return $this->state([
            'title' => 'Diesel',
            'slug' => 'diesel',
            'current_price' => '371.80',
        ]);
    }

    public function petrol()
    {
        return $this->state([
            'title' => 'Petrol',
            'slug' => 'petrol',
            'current_price' => '343.10',
        ]);
    }

    public function highOctane()
    {
        return $this->state([
            'title' => 'High Octane',
            'slug' => 'high-octane',
            'current_price' => '365.00',
        ]);
    }

    // Generic method with custom price
    public function withPrice(float $price)
    {
        return $this->state([
            'current_price' => $price,
        ]);
    }

    // Generic method with custom title and auto-slug
    public function withTitle(string $title)
    {
        return $this->state([
            'title' => $title,
            'slug' => Str::slug($title),
        ]);
    }
}
