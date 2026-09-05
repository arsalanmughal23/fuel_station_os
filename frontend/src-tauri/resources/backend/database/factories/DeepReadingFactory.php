<?php

namespace Database\Factories;

use App\Models\DeepReading;
use App\Models\Tank;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeepReading>
 */
class DeepReadingFactory extends Factory
{
    protected $model = DeepReading::class;

    public function definition(): array
    {
        $deepCm = fake()->randomFloat(2, 0, 300);
        $calibratedVolume = $deepCm * fake()->randomFloat(2, 10, 100);
        $systemStock = $calibratedVolume + fake()->randomFloat(2, -50, 50);
        
        return [
            'tank_id' => Tank::factory(),
            'user_id' => User::factory(),
            'deep_cm' => $deepCm,
            'calibrated_volume_liters' => $calibratedVolume,
            'system_stock_at_reading' => $systemStock,
            'variance_liters' => $calibratedVolume - $systemStock,
            'recorded_at' => now(),
        ];
    }
}