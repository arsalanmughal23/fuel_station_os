<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SalePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_sales');
    }

    public function view(User $user, Sale $sale): bool
    {
        // Staff can only view their own sales
        if ($user->hasRole('staff') && $sale->user_id === $user->id) {
            return true;
        }
        return $user->can('view_sales');
    }

    public function create(User $user): bool
    {
        return $user->can('create_sales');
    }

    public function update(User $user, Sale $sale): bool
    {
        return $user->can('edit_sales');
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $user->can('delete_sales');
    }
}