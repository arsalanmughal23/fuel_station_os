<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'account_type' => fake()->randomElement(array_column(AccountType::cases(), 'value')),
            'name' => fake()->company(),
            'contact' => fake()->phoneNumber(),
            'opening_balance' => fake()->randomFloat(2, -10000, 100000),
            'current_balance' => 0,
        ];
    }
}