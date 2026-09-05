<?php

namespace App\Services;

use App\Models\StockAdjustment;
use App\Models\Tank;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    public function __construct(
        private StockTransactionService $stockTransactionService
    ) {}

    /**
     * Record a stock adjustment and post stock ledger entry
     * 
     * @param array $data {
     *     string stockable_type, // Tank or Product
     *     int stockable_id,
     *     float quantity, // positive = stock-in, negative = stock-out
     *     string unit, // ltr, pcs, box, kg, ml
     *     string adjustment_type, // correction, spillage, evaporation, theft, return, other
     *     string reason,
     *     int|null deep_reading_id, // only for tank adjustments linked to deep reading
     *     datetime|null adjusted_at,
     * }
     */
    public function recordAdjustment(array $data, int $userId): StockAdjustment
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['user_id'] = $userId;
            $data['adjusted_at'] = $data['adjusted_at'] ?? now();

            // Create the stock adjustment record
            $adjustment = StockAdjustment::create($data);

            // Determine the stockable model
            $stockable = match ($adjustment->stockable_type) {
                Tank::class, 'Tank' => Tank::findOrFail($adjustment->stockable_id),
                Product::class, 'Product' => Product::findOrFail($adjustment->stockable_id),
                default => throw new \InvalidArgumentException("Invalid stockable_type: {$adjustment->stockable_type}"),
            };

            // Post stock transaction for the adjustment
            $this->stockTransactionService->append(
                stockable: $stockable,
                quantity: $adjustment->quantity, // Can be positive or negative
                unit: $adjustment->unit,
                userId: $userId,
                sourceFK: ['stock_adjustment_id' => $adjustment->id],
                remarks: "Stock adjustment ({$adjustment->adjustment_type->value}): {$adjustment->reason}"
            );

            return $adjustment->load('stockable', 'deepReading');
        });
    }

    /**
     * Create stock adjustment without posting stock transaction (for manual entry)
     */
    public function create(array $data): StockAdjustment
    {
        return StockAdjustment::create($data);
    }
}
