<?php

namespace Database\Factories;

use App\Models\NozzleReading;
use App\Models\Nozzle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NozzleReading>
 */
class NozzleReadingFactory extends Factory
{
    protected $model = NozzleReading::class;

    public function definition(): array
    {
        $opening = fake()->randomFloat(2, 0, 10000);
        $closing = $opening + fake()->randomFloat(2, 1, 500);
        
        return [
            'nozzle_id' => Nozzle::factory(),
            'user_id' => User::factory(),
            'opening_reading' => $opening,
            'closing_reading' => $closing,
            'liters_sold' => $closing - $opening,
            'price_per_liter' => fake()->randomFloat(2, 50, 200),
            'amount' => ($closing - $opening) * fake()->randomFloat(2, 50, 200),
            'recorded_at' => now(),
        ];
    }
}