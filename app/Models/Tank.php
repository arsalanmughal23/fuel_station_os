<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Tank extends Model
{
    /**
     * calculated_stock is derived from stock_transactions and must not be mass-assigned.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fuel_type_id',
        'name',
        'capacity_liters',
    ];

    protected $casts = [
        'capacity_liters' => 'decimal:3',
        'calculated_stock' => 'decimal:3',
    ];

    protected $guarded = [
        'calculated_stock',
    ];

    public function fuelType(): BelongsTo
    {
        return $this->belongsTo(FuelType::class);
    }

    public function calibrations(): HasMany
    {
        return $this->hasMany(TankCalibration::class);
    }

    public function nozzles(): HasMany
    {
        return $this->hasMany(Nozzle::class);
    }

    public function stockTransactions(): MorphMany
    {
        return $this->morphMany(StockTransaction::class, 'stockable');
    }

    public function deepReadings(): HasMany
    {
        return $this->hasMany(DeepReading::class);
    }

    public function stockAdjustments(): MorphMany
    {
        return $this->morphMany(StockAdjustment::class, 'stockable');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * Get the calculated stock attribute.
     *
     * @return int
     */
    public function getCalculatedStockAttribute($value)
    {
        return $value !== null ? $value : $this->stockTransactions()->sum('quantity');
    }
}
