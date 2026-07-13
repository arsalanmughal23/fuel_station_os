<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected function casts(): array
    {
        return [
            'capacity_liters' => 'decimal:3',
            'calculated_stock' => 'decimal:3',
        ];
    }

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

    public function stockTransactions(): HasMany
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function deepReadings(): HasMany
    {
        return $this->hasMany(DeepReading::class);
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}
