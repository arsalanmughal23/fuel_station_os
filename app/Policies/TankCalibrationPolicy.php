<?php

namespace App\Policies;

use App\Models\TankCalibration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TankCalibrationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_tank_calibrations');
    }

    public function view(User $user, TankCalibration $calibration): bool
    {
        return $user->can('view_tank_calibrations');
    }

    public function create(User $user): bool
    {
        return $user->can('create_tank_calibrations');
    }

    public function update(User $user, TankCalibration $calibration): bool
    {
        return $user->can('edit_tank_calibrations');
    }

    public function delete(User $user, TankCalibration $calibration): bool
    {
        return $user->can('delete_tank_calibrations');
    }
}