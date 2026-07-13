<?php

namespace App\Models;

use App\Models\Concerns\AppendOnlyLedger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransaction extends Model
{
    use AppendOnlyLedger;

    public const UPDATED_AT = null;

    protected $fillable = [
        'tank_id',
        'quantity_liters',
        'balance_after',
        'delivery_id',
        'nozzle_reading_id',
        'stock_adjustment_id',
        'reversed_transaction_id',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'quantity_liters' => 'decimal:3',
            'balance_after' => 'decimal:3',
            'created_at' => 'datetime',
        ];
    }

    public function tank(): BelongsTo
    {
        return $this->belongsTo(Tank::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function nozzleReading(): BelongsTo
    {
        return $this->belongsTo(NozzleReading::class);
    }

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class);
    }

    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_transaction_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversed_transaction_id');
    }
}
