<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class FuelType extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'current_price',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($fuelType) {
            $fuelType->slug = Str::slug($fuelType->title);
        });
    }

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
