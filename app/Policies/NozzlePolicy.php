<?php

namespace App\Policies;

use App\Models\Nozzle;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NozzlePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_nozzles');
    }

    public function view(User $user, Nozzle $nozzle): bool
    {
        return $user->can('view_nozzles');
    }

    public function create(User $user): bool
    {
        return $user->can('create_nozzles');
    }

    public function update(User $user, Nozzle $nozzle): bool
    {
        return $user->can('edit_nozzles');
    }

    public function delete(User $user, Nozzle $nozzle): bool
    {
        return $user->can('delete_nozzles');
    }
}