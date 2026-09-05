<?php

namespace App\Policies;

use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StockTransactionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_stock_transactions');
    }

    public function view(User $user, StockTransaction $stockTransaction): bool
    {
        return $user->can('view_stock_transactions');
    }

    // StockTransactions are append-only - no create/update/delete
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, StockTransaction $stockTransaction): bool
    {
        return false;
    }

    public function delete(User $user, StockTransaction $stockTransaction): bool
    {
        return false;
    }
}