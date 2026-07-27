<?php

namespace App\Models;

use App\Enums\AdjustmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockAdjustment extends Model
{
    protected $fillable = [
        'stockable_type',
        'stockable_id',
        'unit',
        'user_id',
        'deep_reading_id',
        'quantity',
        'adjustment_type',
        'reason',
        'adjusted_at',
    ];

    protected function casts(): array
    {
        return [
            'adjustment_type' => AdjustmentType::class,
            'quantity' => 'decimal:3',
            'adjusted_at' => 'datetime',
        ];
    }

    public function stockable(): MorphTo
    {
        return $this->morphTo();
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
