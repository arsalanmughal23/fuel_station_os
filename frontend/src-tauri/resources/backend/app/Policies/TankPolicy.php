<?php

namespace App\Policies;

use App\Models\Tank;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TankPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_tanks');
    }

    public function view(User $user, Tank $tank): bool
    {
        return $user->can('view_tanks');
    }

    public function create(User $user): bool
    {
        return $user->can('create_tanks');
    }

    public function update(User $user, Tank $tank): bool
    {
        return $user->can('edit_tanks');
    }

    public function delete(User $user, Tank $tank): bool
    {
        return $user->can('delete_tanks');
    }
}