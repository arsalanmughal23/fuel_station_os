<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Account;
use App\Models\FuelType;
use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory()->state(['account_type' => 'distributor']),
            'fuel_type_id' => FuelType::factory(),
            'ordered_liters' => fake()->randomFloat(2, 1000, 50000),
            'price_per_liter' => fake()->randomFloat(2, 50, 200),
            'total_amount' => 0,
            'invoice_number' => fake()->unique()->numerify('PO-####'),
            'status' => PurchaseOrderStatus::Pending,
        ];
    }
}