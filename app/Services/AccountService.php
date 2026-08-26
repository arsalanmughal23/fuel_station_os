<?php

namespace App\Services;

use App\Models\Account;

class AccountService
{
    public function create(array $data): Account
    {
        return Account::create($data);
    }
}
