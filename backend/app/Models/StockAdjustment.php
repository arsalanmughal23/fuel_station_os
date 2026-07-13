<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StockAdjustment extends Model
{
    protected $fillable = [
        'tank_id',
        'user_id',
        'deep_reading_id',
        'quantity_liters',
        'adjustment_type',
        'reason',
        'adjusted_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_liters' => 'decimal:3',
            'adjusted_at' => 'datetime',
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

    public function deepReading(): BelongsTo
    {
        return $this->belongsTo(DeepReading::class);
    }

    public function stockTransaction(): HasOne
    {
        return $this->hasOne(StockTransaction::class);
    }
}
