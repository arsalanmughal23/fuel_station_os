<?php

namespace App\Services;

use App\Models\FuelType;
use App\Models\PriceHistory;
use Illuminate\Support\Facades\DB;

class FuelTypeService
{
    /**
     * Create a new fuel type
     */
    public function create(array $data): FuelType
    {
        return FuelType::create($data);
    }

    /**
     * Update fuel type price and create price history record
     */
    public function updatePrice(FuelType $fuelType, float $newPrice, int $userId, ?string $reason = null): FuelType
    {
        return DB::transaction(function () use ($fuelType, $newPrice, $userId, $reason) {
            $oldPrice = $fuelType->current_price;
            
            // Update the fuel type
            $fuelType->update([
                'current_price' => $newPrice
            ]);
            
            // Create price history record
            PriceHistory::create([
                'priceable_type' => FuelType::class,
                'priceable_id'   => $fuelType->id,
                'old_price'      => $oldPrice,
                'new_price'      => $newPrice,
                'user_id'        => $userId,
                'reason'         => $reason ?? 'Price update',
            ]);
            
            return $fuelType;
        });
    }

    /**
     * Get current price for a fuel type
     */
    public function getCurrentPrice(FuelType $fuelType): float
    {
        return $fuelType->current_price;
    }

    /**
     * Get price history for a fuel type
     */
    public function getPriceHistory(FuelType $fuelType)
    {
        return $fuelType->priceHistory()->orderBy('created_at', 'desc')->get();
    }

}
