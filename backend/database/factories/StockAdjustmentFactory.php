<?php

namespace Database\Factories;

use App\Models\StockAdjustment;
use App\Models\Tank;
use App\Models\Product;
use App\Models\DeepReading;
use App\Models\User;
use App\Enums\AdjustmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustment>
 */
class StockAdjustmentFactory extends Factory
{
    protected $model = StockAdjustment::class;

    public function definition(): array
    {
        $stockableType = fake()->randomElement(['Tank', 'Product']);
        
        return [
            'stockable_type' => $stockableType,
            'stockable_id' => $stockableType === 'Tank' ? Tank::factory() : Product::factory(),
            'user_id' => User::factory(),
            'deep_reading_id' => null,
            'quantity' => fake()->randomFloat(2, -100, 100),
            'unit' => $stockableType === 'Tank' ? 'ltr' : fake()->randomElement(['pcs', 'box', 'kg', 'ml']),
            'adjustment_type' => fake()->randomElement(array_column(AdjustmentType::cases(), 'value')),
            'reason' => fake()->sentence(),
            'adjusted_at' => now(),
        ];
    }
}