<?php

namespace App\Services;

use App\Models\Tank;
use App\Models\TankCalibration;
use Illuminate\Support\Collection;

class TankCalibrationService
{
    /**
     * Get all calibration points for a tank, ordered by deep_cm
     */
    public function getCalibrationPoints(Tank $tank): Collection
    {
        return $tank->calibrations()
            ->orderBy('deep_cm')
            ->get(['deep_cm', 'volume_liters']);
    }

    /**
     * Linear interpolation: deep_cm → volume_liters
     * 
     * @param Tank $tank
     * @param float $deepCm
     * @return float Interpolated volume in liters
     * @throws \InvalidArgumentException if insufficient calibration points
     */
    public function interpolateVolume(Tank $tank, float $deepCm): float
    {
        $points = $this->getCalibrationPoints($tank);

        if ($points->isEmpty()) {
            throw new \InvalidArgumentException("Tank {$tank->name} has no calibration points. Cannot interpolate volume.");
        }

        if ($points->count() === 1) {
            // Only one point - return its volume (or could extrapolate, but safer to return the single point)
            return $points->first()->volume_liters;
        }

        // Find the two calibration points that bracket the deep_cm
        $lower = null;
        $upper = null;

        foreach ($points as $point) {
            if ($point->deep_cm <= $deepCm) {
                $lower = $point;
            }
            if ($point->deep_cm >= $deepCm && $upper === null) {
                $upper = $point;
            }
        }

        // Exact match
        if ($lower && $lower->deep_cm == $deepCm) {
            return $lower->volume_liters;
        }
        if ($upper && $upper->deep_cm == $deepCm) {
            return $upper->volume_liters;
        }

        // Below lowest calibration point - extrapolate using first segment
        if ($lower === null) {
            $first = $points->first();
            $second = $points->skip(1)->first();
            return $this->linearInterpolate($first->deep_cm, $first->volume_liters, $second->deep_cm, $second->volume_liters, $deepCm);
        }

        // Above highest calibration point - extrapolate using last segment
        if ($upper === null) {
            $last = $points->last();
            $secondLast = $points->slice(-2, 1)->first();
            return $this->linearInterpolate($secondLast->deep_cm, $secondLast->volume_liters, $last->deep_cm, $last->volume_liters, $deepCm);
        }

        // Interpolate between lower and upper
        return $this->linearInterpolate($lower->deep_cm, $lower->volume_liters, $upper->deep_cm, $upper->volume_liters, $deepCm);
    }

    /**
     * Linear interpolation helper
     */
    private function linearInterpolate(float $x1, float $y1, float $x2, float $y2, float $x): float
    {
        if ($x2 == $x1) {
            return $y1; // Avoid division by zero
        }

        return $y1 + (($x - $x1) / ($x2 - $x1)) * ($y2 - $y1);
    }

    /**
     * Add a calibration point to a tank
     */
    public function addCalibrationPoint(Tank $tank, float $deepCm, float $volumeLiters): TankCalibration
    {
        return TankCalibration::create([
            'tank_id' => $tank->id,
            'deep_cm' => $deepCm,
            'volume_liters' => $volumeLiters,
        ]);
    }

    /**
     * Create calibration point without validation
     */
    public function create(array $data): TankCalibration
    {
        return TankCalibration::create($data);
    }
}
