<?php

namespace Database\Factories;

use App\Models\Delivery;
use App\Models\PurchaseOrder;
use App\Models\Tank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Delivery>
 */
class DeliveryFactory extends Factory
{
    protected $model = Delivery::class;

    public function definition(): array
    {
        return [
            'purchase_order_id' => PurchaseOrder::factory(),
            'tank_id' => Tank::factory(),
            'vehicle_reg_number' => fake()->licensePlate(),
            'driver_name' => fake()->name(),
            'invoiced_liters' => fake()->randomFloat(2, 1000, 50000),
            'deep_reading_before' => fake()->randomFloat(2, 0, 500),
            'deep_reading_after' => fake()->randomFloat(2, 500, 1000),
            'actual_received_liters' => fake()->randomFloat(2, 1000, 50000),
            'shortage_from_order' => 0,
            'shortage_from_delivery' => 0,
            'received_at' => now(),
        ];
    }
}