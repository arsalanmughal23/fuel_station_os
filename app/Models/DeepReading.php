<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeepReading extends Model
{
    protected $fillable = [
        'tank_id',
        'user_id',
        'deep_cm',
        'calibrated_volume_liters',
        'system_stock_at_reading',
        'variance_liters',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'deep_cm' => 'decimal:3',
            'calibrated_volume_liters' => 'decimal:3',
            'system_stock_at_reading' => 'decimal:3',
            'variance_liters' => 'decimal:3',
            'recorded_at' => 'datetime',
        ];
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }
}
