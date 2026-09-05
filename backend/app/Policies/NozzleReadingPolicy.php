<?php

namespace App\Policies;

use App\Models\NozzleReading;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NozzleReadingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_nozzle_readings');
    }

    public function view(User $user, NozzleReading $nozzleReading): bool
    {
        // Staff can only view their own readings
        if ($user->hasRole('staff') && $nozzleReading->user_id === $user->id) {
            return true;
        }
        return $user->can('view_nozzle_readings');
    }

    public function create(User $user): bool
    {
        return $user->can('create_nozzle_readings');
    }

    public function update(User $user, NozzleReading $nozzleReading): bool
    {
        return $user->can('edit_nozzle_readings');
    }

    public function delete(User $user, NozzleReading $nozzleReading): bool
    {
        return $user->can('delete_nozzle_readings');
    }
}