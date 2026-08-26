<?php

namespace Database\Factories;

use App\Models\SaleItem;
use App\Models\Sale;
use App\Models\Product;
use App\Models\NozzleReading;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    protected $model = SaleItem::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['product', 'fuel']);

        if ($type === 'product') {
            $product = Product::factory()->create();
            $quantity = fake()->randomFloat(2, 1, 10);
            $unitPrice = $product->unit_price;

            return [
                'sale_id' => Sale::factory(),
                'product_id' => $product->id,
                'nozzle_reading_id' => null,
                'unit' => $product->unit,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $quantity * $unitPrice,
            ];
        } else {
            $reading = NozzleReading::factory()->create();

            return [
                'sale_id' => Sale::factory(),
                'product_id' => null,
                'nozzle_reading_id' => $reading->id,
                'unit' => 'ltr',
                'quantity' => $reading->liters_sold,
                'unit_price' => $reading->price_per_liter,
                'amount' => $reading->amount,
            ];
        }
    }
}
