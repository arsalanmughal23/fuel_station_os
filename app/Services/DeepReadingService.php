<?php

namespace App\Services;

use App\Models\DeepReading;
use App\Models\Tank;
use Illuminate\Support\Facades\DB;

class DeepReadingService
{
    public function __construct(
        private TankCalibrationService $calibrationService,
        private StockAdjustmentService $stockAdjustmentService
    ) {}

    /**
     * Record a deep reading (physical dip) and compute variance vs system stock
     * 
     * @param array $data {
     *     int tank_id,
     *     float deep_cm,
     *     datetime|null recorded_at,
     *     bool create_adjustment, // whether to create correction adjustment for variance
     * }
     */
    public function recordReading(array $data, int $userId): DeepReading
    {
        return DB::transaction(function () use ($data, $userId) {
            $tank = Tank::with('calibrations')->findOrFail($data['tank_id']);

            // Get calibrated volume from dip chart using linear interpolation
            $calibratedVolume = $this->calibrationService->interpolateVolume($tank, $data['deep_cm']);

            // Get system stock at time of reading
            $systemStock = $tank->calculated_stock ?? $tank->stockTransactions()->sum('quantity');

            // Compute variance
            $variance = $calibratedVolume - $systemStock;

            // Create the deep reading record
            $reading = DeepReading::create([
                'tank_id' => $data['tank_id'],
                'user_id' => $userId,
                'deep_cm' => $data['deep_cm'],
                'calibrated_volume_liters' => $calibratedVolume,
                'system_stock_at_reading' => $systemStock,
                'variance_liters' => $variance,
                'recorded_at' => $data['recorded_at'] ?? now(),
            ]);

            // Optionally create a correction adjustment for the variance
            if (!empty($data['create_adjustment']) && abs($variance) > 0.001) {
                $this->stockAdjustmentService->recordAdjustment([
                    'stockable_type' => Tank::class,
                    'stockable_id' => $tank->id,
                    'quantity' => $variance, // Positive if physical > system, negative if physical < system
                    'unit' => 'ltr',
                    'adjustment_type' => 'correction',
                    'reason' => "Deep reading correction: physical dip {$data['deep_cm']} cm = {$calibratedVolume} L, system had {$systemStock} L, variance {$variance} L",
                    'deep_reading_id' => $reading->id,
                    'adjusted_at' => $reading->recorded_at,
                ], $userId);
            }

            return $reading->load('tank');
        });
    }

    /**
     * Create deep reading without computing variance or creating adjustment
     */
    public function create(array $data): DeepReading
    {
        return DeepReading::create($data);
    }

    /**
     * Get variance history for a tank
     */
    public function getVarianceHistory(int $tankId, int $limit = 50)
    {
        return DeepReading::where('tank_id', $tankId)
            ->latest('recorded_at')
            ->limit($limit)
            ->get(['recorded_at', 'deep_cm', 'calibrated_volume_liters', 'system_stock_at_reading', 'variance_liters']);
    }
}
