<?php

namespace App\Policies;

use App\Models\Delivery;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeliveryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_deliveries');
    }

    public function view(User $user, Delivery $delivery): bool
    {
        return $user->can('view_deliveries');
    }

    public function create(User $user): bool
    {
        return $user->can('create_deliveries');
    }

    public function update(User $user, Delivery $delivery): bool
    {
        return $user->can('edit_deliveries');
    }

    public function delete(User $user, Delivery $delivery): bool
    {
        return $user->can('delete_deliveries');
    }
}