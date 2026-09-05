<?php

namespace App\Models;

use App\Enums\ScaleUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    use Concerns\HasSlug;

    /**
     * current_stock is a derived persisted field that tracks inventory levels.
     * It should not be mass-assigned directly.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'category',
        'unit',
        'unit_price',
    ];

    protected $guarded = [
        'current_stock',
    ];

    protected $casts = [
        'unit' => ScaleUnit::class,
        'unit_price' => 'decimal:10,4',
        'current_stock' => 'decimal:10,2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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

    public function getCurrentStockAttribute($value)
    {
        return $value !== null ? $value : $this->stockTransactions()->sum('quantity');
    }
}
