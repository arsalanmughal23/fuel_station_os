<?php

namespace App\Models;

use App\Enums\ScaleUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Product extends Model
{
    /**
     * current_stock is a persisted field that tracks inventory levels.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'category',
        'unit',
        'unit_price',
        'current_stock',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            $product->slug = Str::slug($product->title);
        });
    }

    protected function casts(): array
    {
        return [
            'unit' => ScaleUnit::class,
            'unit_price' => 'decimal:10,4',
            'current_stock' => 'decimal:10,2',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function stockTransactions(): MorphMany
    {
        return $this->morphMany(StockTransaction::class, 'stockable');
    }

    public function stockAdjustments(): MorphMany
    {
        return $this->morphMany(StockAdjustment::class, 'stockable');
    }

    public function priceHistory(): MorphMany
    {
        return $this->morphMany(PriceHistory::class, 'priceable');
    }

    /**
     * Get the current stock level.
     *
     * @return float
     */
    public function getCurrentStockAttribute()
    {
        return $this->current_stock;
    }
}