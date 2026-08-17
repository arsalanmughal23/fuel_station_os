<?php

namespace App\Models;

use App\Models\Concerns\AppendOnlyLedger;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockTransaction extends Model
{
    use AppendOnlyLedger;

    public const UPDATED_AT = null;

    protected static function booted(): void
    {
        static::created(function (self $transaction) {
            if (in_array($transaction->stockable_type, [Tank::class, 'Tank'], true)) {
                $tank = Tank::find($transaction->stockable_id);

                if (! $tank) {
                    return;
                }

                $tank->forceFill([
                    'calculated_stock' => $tank->stockTransactions()->sum('quantity'),
                ])->saveQuietly();

                return;
            }

            if (in_array($transaction->stockable_type, [Product::class, 'Product'], true)) {
                $product = Product::find($transaction->stockable_id);

                if (! $product) {
                    return;
                }

                $product->forceFill([
                    'current_stock' => $product->stockTransactions()->sum('quantity'),
                ])->saveQuietly();
            }
        });
    }

    protected $fillable = [
        'stockable_type',
        'stockable_id',
        'unit',
        'quantity',
        'balance_after',
        'user_id',
        'delivery_id',
        'nozzle_reading_id',
        'sale_item_id',
        'stock_adjustment_id',
        'reversed_transaction_id',
        'remarks',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'balance_after' => 'decimal:3',
        'created_at' => 'datetime',
    ];

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    public function nozzleReading(): BelongsTo
    {
        return $this->belongsTo(NozzleReading::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
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
