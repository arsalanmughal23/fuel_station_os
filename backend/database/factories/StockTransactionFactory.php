<?php

namespace Database\Factories;

use App\Models\StockTransaction;
use App\Models\Tank;
use App\Models\Product;
use App\Models\Delivery;
use App\Models\NozzleReading;
use App\Models\SaleItem;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransaction>
 */
class StockTransactionFactory extends Factory
{
    protected $model = StockTransaction::class;

    public function definition(): array
    {
        $stockableType = fake()->randomElement(['Tank', 'Product']);
        $sourceType = fake()->randomElement(['delivery', 'nozzle_reading', 'sale_item', 'stock_adjustment', 'reversal']);
        
        $data = [
            'stockable_type' => $stockableType,
            'stockable_id' => $stockableType === 'Tank' ? Tank::factory() : Product::factory(),
            'quantity' => fake()->randomFloat(2, -100, 100),
            'unit' => $stockableType === 'Tank' ? 'ltr' : fake()->randomElement(['pcs', 'box', 'kg', 'ml']),
            'balance_after' => 0, // Will be calculated
            'user_id' => User::factory(),
            'delivery_id' => null,
            'nozzle_reading_id' => null,
            'sale_item_id' => null,
            'stock_adjustment_id' => null,
            'reversed_transaction_id' => null,
            'remarks' => fake()->sentence(),
        ];

        // Set exactly one source FK
        switch ($sourceType) {
            case 'delivery':
                $data['delivery_id'] = Delivery::factory();
                break;
            case 'nozzle_reading':
                $data['nozzle_reading_id'] = NozzleReading::factory();
                break;
            case 'sale_item':
                $data['sale_item_id'] = SaleItem::factory();
                break;
            case 'stock_adjustment':
                $data['stock_adjustment_id'] = StockAdjustment::factory();
                break;
            case 'reversal':
                $data['reversed_transaction_id'] = StockTransaction::factory();
                break;
        }

        return $data;
    }
}