<?php

namespace Database\Factories;

use App\Models\PriceHistory;
use App\Models\FuelType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceHistory>
 */
class PriceHistoryFactory extends Factory
{
    protected $model = PriceHistory::class;

    public function definition(): array
    {
        $priceableType = fake()->randomElement(['FuelType', 'Product']);
        
        return [
            'priceable_type' => $priceableType,
            'priceable_id' => $priceableType === 'FuelType' ? FuelType::factory() : Product::factory(),
            'old_price' => fake()->randomFloat(2, 10, 500),
            'new_price' => fake()->randomFloat(2, 10, 500),
            'user_id' => User::factory(),
            'reason' => fake()->sentence(),
            'changed_at' => now(),
        ];
    }
}