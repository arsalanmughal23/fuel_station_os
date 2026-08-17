<?php

namespace App\Services;

use App\Models\TankCalibration;

class TankCalibrationService
{
    public function create(array $data): TankCalibration
    {
        return TankCalibration::create($data);
    }
}
