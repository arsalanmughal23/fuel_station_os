<?php

namespace App\Policies;

use App\Models\DeepReading;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DeepReadingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_deep_readings');
    }

    public function view(User $user, DeepReading $deepReading): bool
    {
        // Staff can only view their own readings
        if ($user->hasRole('staff') && $deepReading->user_id === $user->id) {
            return true;
        }
        return $user->can('view_deep_readings');
    }

    public function create(User $user): bool
    {
        return $user->can('create_deep_readings');
    }

    public function update(User $user, DeepReading $deepReading): bool
    {
        return $user->can('edit_deep_readings');
    }

    public function delete(User $user, DeepReading $deepReading): bool
    {
        return $user->can('delete_deep_readings');
    }
}