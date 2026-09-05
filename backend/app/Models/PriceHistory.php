<?php

namespace App\Models;

use App\Models\FuelType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PriceHistory extends Model
{
    protected $table = 'price_history';

    protected $fillable = [
        'priceable_type',
        'priceable_id',
        'old_price',
        'new_price',
        'user_id',
        'reason',
        'changed_at',
    ];

    protected $casts = [
        'old_price' => 'decimal:4',
        'new_price' => 'decimal:4',
        'changed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $history) {
            $priceable = $history->priceable;

            if (! $priceable) {
                return;
            }

            if ($priceable instanceof FuelType) {
                $priceable->forceFill(['current_price' => $history->new_price])->saveQuietly();
                return;
            }

            if ($priceable instanceof Product) {
                $priceable->forceFill(['unit_price' => $history->new_price])->saveQuietly();
            }
        });
    }

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
