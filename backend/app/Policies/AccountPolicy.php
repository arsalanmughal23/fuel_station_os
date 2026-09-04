<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_accounts');
    }

    public function view(User $user, Account $account): bool
    {
        // Users can view accounts they have access to
        // Employee accounts can only view their own account
        if ($account->account_type === 'employee' && $account->user_id === $user->id) {
            return true;
        }
        return $user->can('view_accounts');
    }

    public function create(User $user): bool
    {
        return $user->can('create_accounts');
    }

    public function update(User $user, Account $account): bool
    {
        // Users can update their own employee account details
        if ($account->account_type === 'employee' && $account->user_id === $user->id) {
            return true;
        }
        return $user->can('edit_accounts');
    }

    public function delete(User $user, Account $account): bool
    {
        return $user->can('delete_accounts');
    }
}