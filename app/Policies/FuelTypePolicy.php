<?php

namespace App\Policies;

use App\Models\FuelType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FuelTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_fuel_types');
    }

    public function view(User $user, FuelType $fuelType): bool
    {
        return $user->can('view_fuel_types');
    }

    public function create(User $user): bool
    {
        return $user->can('create_fuel_types');
    }

    public function update(User $user, FuelType $fuelType): bool
    {
        return $user->can('edit_fuel_types');
    }

    public function delete(User $user, FuelType $fuelType): bool
    {
        return $user->can('delete_fuel_types');
    }
}