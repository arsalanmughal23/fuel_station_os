<?php

namespace App\Policies;

use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PaymentTransactionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_payment_transactions');
    }

    public function view(User $user, PaymentTransaction $paymentTransaction): bool
    {
        // Users can view payments for their own accounts
        if ($paymentTransaction->account->user_id === $user->id) {
            return true;
        }
        return $user->can('view_payment_transactions');
    }

    // PaymentTransactions are append-only - no create/update/delete via API
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PaymentTransaction $paymentTransaction): bool
    {
        return false;
    }

    public function delete(User $user, PaymentTransaction $paymentTransaction): bool
    {
        return false;
    }
}