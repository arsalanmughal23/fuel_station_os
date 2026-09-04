<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\User;
use App\Models\Account;
use App\Enums\SalePaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => null,
            'total_amount' => 0,
            'paid_amount' => 0,
            'change_amount' => 0,
            'payment_status' => SalePaymentStatus::Pending,
            'sale_date' => now(),
        ];
    }
}