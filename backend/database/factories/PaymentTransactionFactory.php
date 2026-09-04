<?php

namespace Database\Factories;

use App\Models\PaymentTransaction;
use App\Models\Account;
use App\Models\Sale;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Enums\PaymentType;
use App\Enums\PaymentCategory;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'type' => fake()->randomElement(array_column(PaymentType::cases(), 'value')),
            'category' => fake()->randomElement(array_column(PaymentCategory::cases(), 'value')),
            'amount' => fake()->randomFloat(2, 10, 10000),
            'payment_method' => fake()->randomElement(array_column(PaymentMethod::cases(), 'value')),
            'sale_id' => null,
            'purchase_order_id' => null,
            'reversed_transaction_id' => null,
            'status' => PaymentStatus::Completed,
            'remarks' => fake()->sentence(),
            'transacted_at' => now(),
        ];
    }
}