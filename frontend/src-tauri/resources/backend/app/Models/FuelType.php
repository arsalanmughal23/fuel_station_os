<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FuelType extends Model
{
    use HasFactory, Concerns\HasSlug;

    protected $fillable = [
        'title',
        'current_price',
    ];

    protected $casts = [
        'current_price' => 'decimal:2',
    ];

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
