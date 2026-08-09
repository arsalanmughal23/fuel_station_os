<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class FuelType extends Model
{
    use Concerns\HasSlug;

    protected $fillable = [
        'title',
        'current_price',
    ];

    protected function casts(): array
    {
        return [
            'current_price' => 'decimal:2',
        ];
    }

    public function tanks(): HasMany
    {
        return $this->hasMany(Tank::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function priceHistory(): MorphMany
    {
        return $this->morphMany(PriceHistory::class, 'priceable');
    }
}
